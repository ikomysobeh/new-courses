<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationConfig;
use App\Models\EvaluationType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEvaluationApiTest extends TestCase
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

    private function userToken(User $user): string
    {
        return (string) $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->json('data.token');
    }

    private function seedUserEvaluation(User $user): Evaluation
    {
        $dept   = Department::create(['name' => 'Eng', 'slug' => 'eng']);
        $admin  = User::where('role', 'admin')->first();
        $course = Course::factory()->create(['status' => 'published', 'privacy' => 'public', 'created_by' => $admin->id]);

        $config = EvaluationConfig::create(['name' => 'Attendance', 'max_score' => 5, 'applies_to' => 'both']);
        $type   = EvaluationType::create([
            'evaluation_config_id' => $config->id,
            'type_name'            => 'Full',
            'score_value'          => 5,
        ]);

        return Evaluation::create([
            'user_id'       => $user->id,
            'department_id' => $dept->id,
            'course_type'   => 'regular',
            'course_id'     => $course->id,
            'total_score'   => 5,
        ]);
    }

    public function test_user_can_see_own_evaluations(): void
    {
        $this->adminToken(); // seeds DB
        $user  = User::factory()->create();
        $token = $this->userToken($user);

        $this->seedUserEvaluation($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/evaluations/getAll');

        $response->assertOk();
    }

    public function test_user_can_get_own_evaluation_by_id(): void
    {
        $this->adminToken();
        $user  = User::factory()->create();
        $token = $this->userToken($user);
        $eval  = $this->seedUserEvaluation($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/user/evaluations/getById/{$eval->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $eval->id);
    }

    public function test_user_cannot_get_another_users_evaluation(): void
    {
        $this->adminToken();
        $user1  = User::factory()->create();
        $user2  = User::factory()->create();
        $token2 = $this->userToken($user2);

        $eval = $this->seedUserEvaluation($user1); // evaluation belongs to user1

        $response = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->getJson("/api/user/evaluations/getById/{$eval->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_evaluations(): void
    {
        $response = $this->getJson('/api/user/evaluations/getAll');

        $response->assertUnauthorized();
    }
}
