<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationConfig;
use App\Models\EvaluationHistory;
use App\Models\EvaluationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEvaluationHistoryApiTest extends TestCase
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

    private function seedEvaluation(): Evaluation
    {
        $dept   = Department::create(['name' => 'Eng', 'slug' => 'eng']);
        $admin  = User::where('role', 'admin')->first();
        $user   = User::factory()->create(['department_id' => $dept->id]);
        $course = Course::factory()->create(['status' => 'published', 'privacy' => 'public', 'created_by' => $admin->id]);

        $config = EvaluationConfig::create(['name' => 'Attendance', 'max_score' => 5, 'applies_to' => 'both']);
        $type   = EvaluationType::create([
            'evaluation_config_id' => $config->id,
            'type_name'            => 'Full',
            'score_value'          => 5,
        ]);

        $eval = Evaluation::create([
            'user_id'       => $user->id,
            'department_id' => $dept->id,
            'course_type'   => 'regular',
            'course_id'     => $course->id,
            'total_score'   => 5,
        ]);

        EvaluationHistory::create([
            'evaluation_id' => $eval->id,
            'config_name'   => 'Attendance',
            'type_name'     => 'Full',
            'score_given'   => 5,
            'max_score'     => 5,
        ]);

        return $eval;
    }

    public function test_admin_can_get_all_history(): void
    {
        $token = $this->adminToken();
        $this->seedEvaluation();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluation-history/getAll');

        $response->assertOk();
    }

    public function test_admin_can_get_history_by_id(): void
    {
        $token = $this->adminToken();
        $eval  = $this->seedEvaluation();

        $history = EvaluationHistory::where('evaluation_id', $eval->id)->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/evaluation-history/getById/{$history->id}");

        $response->assertOk()
            ->assertJsonPath('data.history.0.config_name', 'Attendance');
    }

    public function test_admin_can_get_analytics(): void
    {
        $token = $this->adminToken();
        $this->seedEvaluation();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluation-history/analytics');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['total_evaluations', 'average_score', 'performance_distribution', 'monthly_trends', 'top_categories']]);
    }

    public function test_admin_can_export_history_as_csv(): void
    {
        $token = $this->adminToken();
        $this->seedEvaluation();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluation-history/export');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_export_summary_csv(): void
    {
        $token = $this->adminToken();
        $this->seedEvaluation();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluation-history/export-summary');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_history_can_be_filtered_by_course_type(): void
    {
        $token = $this->adminToken();
        $this->seedEvaluation();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/evaluation-history/getAll?course_type=regular');

        $response->assertOk();
    }
}
