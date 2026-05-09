<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuizApiTest extends TestCase
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

    private function adminHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->adminToken()];
    }

    public function test_create_quiz_returns_draft_status(): void
    {
        $course = Course::factory()->create();
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/quizzes/create', [
                'title'    => 'Safety Quiz',
                'course_id' => $course->id,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'draft');
        $response->assertJsonPath('data.title', 'Safety Quiz');
        $this->assertDatabaseHas('quizzes', ['title' => 'Safety Quiz', 'status' => 'draft']);
    }

    public function test_create_quiz_with_questions_computes_total_points(): void
    {
        $course = Course::factory()->create();
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/quizzes/create', [
                'title'     => 'Quiz With Questions',
                'course_id' => $course->id,
                'questions' => [
                    [
                        'question_text'  => 'What is 2+2?',
                        'type'           => 'radio',
                        'points'         => 10,
                        'options'        => ['3', '4', '5'],
                        'correct_answer' => ['4'],
                    ],
                    [
                        'question_text'  => 'Select all primes.',
                        'type'           => 'checkbox',
                        'points'         => 5,
                        'options'        => ['2', '3', '4'],
                        'correct_answer' => ['2', '3'],
                    ],
                    [
                        'question_text' => 'Explain safety rules.',
                        'type'          => 'text',
                    ],
                ],
            ]);

        $response->assertCreated();
        $quiz = Quiz::query()->first();
        $this->assertSame(15, $quiz->total_points);
        $this->assertDatabaseCount('quiz_questions', 3);
    }

    public function test_cannot_create_quiz_with_more_than_one_ownership_fk(): void
    {
        $course = Course::factory()->create();
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/quizzes/create', [
                'title'            => 'Quiz',
                'course_id'        => $course->id,
                'course_online_id' => 99,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('ownership');
    }

    public function test_update_quiz_fields(): void
    {
        $course = Course::factory()->create();
        $token = $this->adminToken();
        $quiz = Quiz::query()->create([
            'title'     => 'Old Title',
            'course_id' => $course->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/quizzes/update/{$quiz->id}", [
                'title'       => 'New Title',
                'max_attempts' => 5,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.title', 'New Title');
        $response->assertJsonPath('data.max_attempts', 5);
    }

    public function test_cannot_publish_quiz_with_zero_questions(): void
    {
        $course = Course::factory()->create();
        $token = $this->adminToken();
        $quiz = Quiz::query()->create(['title' => 'Empty Quiz', 'course_id' => $course->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/quizzes/update/{$quiz->id}", ['status' => 'published']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('status');
    }

    public function test_delete_quiz_soft_deletes(): void
    {
        $course = Course::factory()->create();
        $token = $this->adminToken();
        $quiz = Quiz::query()->create(['title' => 'To Delete', 'course_id' => $course->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/quizzes/delete/{$quiz->id}");

        $response->assertOk();
        $this->assertSoftDeleted('quizzes', ['id' => $quiz->id]);
    }

    public function test_cannot_delete_quiz_with_completed_attempts(): void
    {
        $course = Course::factory()->create();
        $token = $this->adminToken();
        $quiz = Quiz::query()->create(['title' => 'Active Quiz', 'course_id' => $course->id]);
        $user = User::factory()->create();

        \App\Models\QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'completed_at'   => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/quizzes/delete/{$quiz->id}");

        $response->assertUnprocessable();
        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id, 'deleted_at' => null]);
    }

    public function test_non_admin_cannot_access_quiz_endpoints(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/quizzes/getAll')
            ->assertForbidden();
    }
}
