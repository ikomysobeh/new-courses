<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuizAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $this->seed(DatabaseSeeder::class);

        return (string) $this->postJson('/api/login', [
            'email' => 'admin@newproject.test',
            'password' => env('ADMIN_INITIAL_PASSWORD', 'Admin@12345'),
        ])->json('data.token');
    }

    private function createQuiz(): Quiz
    {
        $course = Course::factory()->create();

        return Quiz::query()->create(['title' => 'Assigned Quiz', 'course_id' => $course->id]);
    }

    public function test_assign_quiz_to_multiple_users(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createQuiz();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/quiz-assignments/create', [
                'quiz_id'  => $quiz->id,
                'user_ids' => [$userA->id, $userB->id],
            ]);

        $response->assertCreated()->assertJsonCount(2, 'data');
        $this->assertDatabaseCount('quiz_assignments', 2);
    }

    public function test_assign_quiz_to_already_assigned_user_silently_skipped(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createQuiz();
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $admin = User::query()->where('email', 'admin@newproject.test')->firstOrFail();

        QuizAssignment::query()->create([
            'quiz_id'     => $quiz->id,
            'user_id'     => $userA->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/quiz-assignments/create', [
                'quiz_id'  => $quiz->id,
                'user_ids' => [$userA->id, $userB->id],
            ]);

        $response->assertCreated()->assertJsonCount(1, 'data');
        $this->assertDatabaseCount('quiz_assignments', 2);
    }

    public function test_delete_quiz_assignment(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createQuiz();
        $user = User::factory()->create();
        $admin = User::query()->where('email', 'admin@newproject.test')->firstOrFail();

        $assignment = QuizAssignment::query()->create([
            'quiz_id'     => $quiz->id,
            'user_id'     => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/quiz-assignments/delete/{$assignment->id}")
            ->assertOk();

        $this->assertSoftDeleted('quiz_assignments', ['id' => $assignment->id]);
    }
}
