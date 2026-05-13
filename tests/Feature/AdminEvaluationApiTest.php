<?php

namespace Tests\Feature;

use App\Enums\PerformanceLevel;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseOnline;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationConfig;
use App\Models\EvaluationHistory;
use App\Models\EvaluationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEvaluationApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        return (string) $this->postJson('/api/login', [
            'email'    => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ])->json('data.token');
    }

    private function setupEvaluationFixtures(): array
    {
        $dept = Department::create(['name' => 'Engineering', 'slug' => 'engineering']);
        $user = User::factory()->create(['department_id' => $dept->id, 'role' => 'user']);

        $config = EvaluationConfig::create(['name' => 'Attendance', 'max_score' => 5, 'applies_to' => 'both']);
        $type   = EvaluationType::create([
            'evaluation_config_id' => $config->id,
            'type_name'            => 'Full Attendance',
            'score_value'          => 5,
        ]);

        $admin  = User::where('role', 'admin')->first();
        $course = Course::factory()->create(['status' => 'published', 'privacy' => 'public', 'created_by' => $admin->id]);

        // Assign user to course so verifyCourseAssignment() passes
        CourseAssignment::create([
            'course_id'   => $course->id,
            'user_id'     => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        return compact('dept', 'user', 'config', 'type', 'course');
    }

    public function test_admin_can_create_evaluation_for_regular_course(): void
    {
        $token   = $this->adminToken();
        $fixtures = $this->setupEvaluationFixtures();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluations/create', [
                'user_id'       => $fixtures['user']->id,
                'department_id' => $fixtures['dept']->id,
                'course_type'   => 'regular',
                'course_id'     => $fixtures['course']->id,
                'scores'        => [
                    ['evaluation_type_id' => $fixtures['type']->id, 'score_given' => 5],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.course_type', 'regular')
            ->assertJsonPath('data.total_score', 5);

        $this->assertDatabaseHas('evaluations', ['user_id' => $fixtures['user']->id]);
        $this->assertDatabaseHas('evaluation_histories', [
            'config_name' => 'Attendance',
            'score_given' => 5,
        ]);
    }

    public function test_server_calculates_total_score_not_client(): void
    {
        $token    = $this->adminToken();
        $fixtures = $this->setupEvaluationFixtures();

        // Client sends total_score = 99 — server must ignore and calculate from scores[]
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluations/create', [
                'user_id'       => $fixtures['user']->id,
                'department_id' => $fixtures['dept']->id,
                'course_type'   => 'regular',
                'course_id'     => $fixtures['course']->id,
                'total_score'   => 99, // should be ignored
                'scores'        => [
                    ['evaluation_type_id' => $fixtures['type']->id, 'score_given' => 3],
                ],
            ]);

        $response->assertCreated()->assertJsonPath('data.total_score', 3);
    }

    public function test_evaluation_update_or_create_replaces_not_duplicates(): void
    {
        $token    = $this->adminToken();
        $fixtures = $this->setupEvaluationFixtures();

        // Create first time
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluations/create', [
                'user_id'       => $fixtures['user']->id,
                'department_id' => $fixtures['dept']->id,
                'course_type'   => 'regular',
                'course_id'     => $fixtures['course']->id,
                'scores'        => [
                    ['evaluation_type_id' => $fixtures['type']->id, 'score_given' => 3],
                ],
            ])->assertCreated();

        // Create again with same key → should update, not create duplicate
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluations/create', [
                'user_id'       => $fixtures['user']->id,
                'department_id' => $fixtures['dept']->id,
                'course_type'   => 'regular',
                'course_id'     => $fixtures['course']->id,
                'scores'        => [
                    ['evaluation_type_id' => $fixtures['type']->id, 'score_given' => 5],
                ],
            ])->assertSuccessful(); // 200 or 201 — controller always returns 201 for upserts

        $this->assertDatabaseCount('evaluations', 1);
        $this->assertDatabaseHas('evaluations', ['total_score' => 5]);
    }

    public function test_history_rows_are_replaced_on_update(): void
    {
        $token    = $this->adminToken();
        $fixtures = $this->setupEvaluationFixtures();

        $type2 = EvaluationType::create([
            'evaluation_config_id' => $fixtures['config']->id,
            'type_name'            => 'Partial',
            'score_value'          => 3,
        ]);

        // First submission
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluations/create', [
                'user_id'       => $fixtures['user']->id,
                'department_id' => $fixtures['dept']->id,
                'course_type'   => 'regular',
                'course_id'     => $fixtures['course']->id,
                'scores'        => [
                    ['evaluation_type_id' => $fixtures['type']->id, 'score_given' => 5],
                    ['evaluation_type_id' => $type2->id,             'score_given' => 3],
                ],
            ]);

        // Second submission updates history rows (should replace)
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluations/create', [
                'user_id'       => $fixtures['user']->id,
                'department_id' => $fixtures['dept']->id,
                'course_type'   => 'regular',
                'course_id'     => $fixtures['course']->id,
                'scores'        => [
                    ['evaluation_type_id' => $fixtures['type']->id, 'score_given' => 2],
                ],
            ]);

        // Only one history row should exist for the one type submitted
        $evaluation = Evaluation::first();
        $this->assertDatabaseCount('evaluation_histories', 1);
        $this->assertDatabaseHas('evaluation_histories', ['score_given' => 2]);
    }

    public function test_performance_level_derived_from_enum(): void
    {
        $token    = $this->adminToken();
        $fixtures = $this->setupEvaluationFixtures();

        // Score 5 → let PerformanceLevel decide level
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluations/create', [
                'user_id'       => $fixtures['user']->id,
                'department_id' => $fixtures['dept']->id,
                'course_type'   => 'regular',
                'course_id'     => $fixtures['course']->id,
                'scores'        => [
                    ['evaluation_type_id' => $fixtures['type']->id, 'score_given' => 5],
                ],
            ]);

        $level = PerformanceLevel::getLevelByScore(5);
        $response->assertJsonPath('data.performance_level.level', $level);
    }

    public function test_bulk_create_evaluations(): void
    {
        $token    = $this->adminToken();
        $fixtures = $this->setupEvaluationFixtures();

        $user2 = User::factory()->create(['department_id' => $fixtures['dept']->id]);

        // Assign user2 to course so verifyCourseAssignment() passes
        CourseAssignment::create([
            'course_id'   => $fixtures['course']->id,
            'user_id'     => $user2->id,
            'assigned_by' => User::where('role', 'admin')->first()->id,
            'assigned_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/evaluations/bulk-create', [
                'evaluations' => [
                    [
                        'user_id'       => $fixtures['user']->id,
                        'department_id' => $fixtures['dept']->id,
                        'course_type'   => 'regular',
                        'course_id'     => $fixtures['course']->id,
                        'scores'        => [['evaluation_type_id' => $fixtures['type']->id, 'score_given' => 5]],
                    ],
                    [
                        'user_id'       => $user2->id,
                        'department_id' => $fixtures['dept']->id,
                        'course_type'   => 'regular',
                        'course_id'     => $fixtures['course']->id,
                        'scores'        => [['evaluation_type_id' => $fixtures['type']->id, 'score_given' => 3]],
                    ],
                ],
            ]);

        $response->assertOk();
        $this->assertDatabaseCount('evaluations', 2);
    }

    public function test_admin_can_delete_evaluation(): void
    {
        $token    = $this->adminToken();
        $fixtures = $this->setupEvaluationFixtures();

        $eval = Evaluation::create([
            'user_id'       => $fixtures['user']->id,
            'department_id' => $fixtures['dept']->id,
            'course_type'   => 'regular',
            'course_id'     => $fixtures['course']->id,
            'total_score'   => 5,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/evaluations/delete/{$eval->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('evaluations', ['id' => $eval->id]);
    }

    public function test_admin_can_get_all_evaluations(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluations/getAll');

        $response->assertOk();
    }

    public function test_admin_can_get_users_with_courses_by_department(): void
    {
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluations/users');

        $response->assertOk();
    }

    public function test_admin_can_get_user_courses(): void
    {
        $token    = $this->adminToken();
        $fixtures = $this->setupEvaluationFixtures();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/evaluations/user-courses?user_id={$fixtures['user']->id}");

        $response->assertOk();
    }
}
