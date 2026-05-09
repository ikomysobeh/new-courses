<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuizAttemptApiTest extends TestCase
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

    private function createPublishedQuiz(): Quiz
    {
        $course = Course::factory()->create();
        $quiz = Quiz::query()->create([
            'title'         => 'Test Quiz',
            'course_id'     => $course->id,
            'status'        => 'published',
            'total_points'  => 15,
            'pass_threshold' => 80.00,
        ]);
        QuizQuestion::query()->create([
            'quiz_id'        => $quiz->id,
            'question_text'  => 'What is 2+2?',
            'type'           => 'radio',
            'points'         => 10,
            'options'        => ['3', '4', '5'],
            'correct_answer' => ['4'],
        ]);
        QuizQuestion::query()->create([
            'quiz_id'       => $quiz->id,
            'question_text' => 'Explain SQL.',
            'type'          => 'text',
        ]);

        return $quiz;
    }

    public function test_admin_can_view_all_attempts_for_quiz(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createPublishedQuiz();
        $user = User::factory()->create();

        QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'completed_at'   => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/quizzes/{$quiz->id}/attempts/getAll");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_view_attempt_with_all_answers(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createPublishedQuiz();
        $user = User::factory()->create();

        $attempt = QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'completed_at'   => now(),
        ]);

        $question = $quiz->questions()->first();
        QuizAnswer::query()->create([
            'quiz_attempt_id'  => $attempt->id,
            'quiz_question_id' => $question->id,
            'answer'           => '4',
            'is_correct'       => 1,
            'points_earned'    => 10,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/admin/quizzes/{$quiz->id}/attempts/getById/{$attempt->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'answers']]);
    }

    public function test_manual_grade_saves_points_and_is_correct(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createPublishedQuiz();
        $user = User::factory()->create();

        $attempt = QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'completed_at'   => now(),
            'total_points'   => 15,
        ]);

        $textQuestion = $quiz->questions()->where('type', 'text')->first();
        $answer = QuizAnswer::query()->create([
            'quiz_attempt_id'  => $attempt->id,
            'quiz_question_id' => $textQuestion->id,
            'answer'           => 'SQL is a query language.',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/quiz-answers/grade/{$answer->id}", [
                'points_earned' => 5,
            ]);

        $response->assertOk();
        $answer->refresh();
        $this->assertSame(5, $answer->points_earned);
        $this->assertTrue((bool) $answer->is_correct);
    }

    public function test_finalize_score_updates_passed_after_manual_grade(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createPublishedQuiz();
        $user = User::factory()->create();

        $attempt = QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'completed_at'   => now(),
            'score'          => 10,
            'manual_score'   => 0,
            'total_score'    => 10,
            'passed'         => false,
        ]);

        $textQuestion = $quiz->questions()->where('type', 'text')->first();
        $answer = QuizAnswer::query()->create([
            'quiz_attempt_id'  => $attempt->id,
            'quiz_question_id' => $textQuestion->id,
            'answer'           => 'Great answer',
            'points_earned'    => 0,
        ]);

        $radioQuestion = $quiz->questions()->where('type', 'radio')->first();
        QuizAnswer::query()->create([
            'quiz_attempt_id'  => $attempt->id,
            'quiz_question_id' => $radioQuestion->id,
            'answer'           => '4',
            'is_correct'       => 1,
            'points_earned'    => 10,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/quiz-answers/grade/{$answer->id}", [
                'points_earned' => 5,
            ])
            ->assertOk();

        $attempt->refresh();
        $this->assertSame(5, $attempt->manual_score);
        $this->assertSame(15, $attempt->total_score);
        $this->assertTrue((bool) $attempt->passed);
    }
}
