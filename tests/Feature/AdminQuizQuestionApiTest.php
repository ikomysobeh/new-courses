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

class AdminQuizQuestionApiTest extends TestCase
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

        return Quiz::query()->create([
            'title'     => 'Test Quiz',
            'course_id' => $course->id,
        ]);
    }

    public function test_add_radio_question_increases_total_points(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createQuiz();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/quizzes/{$quiz->id}/questions/create", [
                'question_text'  => 'What is PHP?',
                'type'           => 'radio',
                'points'         => 10,
                'options'        => ['A language', 'A database', 'An OS'],
                'correct_answer' => ['A language'],
            ])
            ->assertCreated();

        $quiz->refresh();
        $this->assertSame(10, $quiz->total_points);
    }

    public function test_add_checkbox_question_increases_total_points(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createQuiz();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/quizzes/{$quiz->id}/questions/create", [
                'question_text'  => 'Select valid HTTP methods.',
                'type'           => 'checkbox',
                'points'         => 5,
                'options'        => ['GET', 'POST', 'PAINT'],
                'correct_answer' => ['GET', 'POST'],
            ])
            ->assertCreated();

        $quiz->refresh();
        $this->assertSame(5, $quiz->total_points);
    }

    public function test_add_text_question_does_not_increase_total_points(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createQuiz();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/quizzes/{$quiz->id}/questions/create", [
                'question_text' => 'Explain the OSI model.',
                'type'          => 'text',
            ])
            ->assertCreated();

        $quiz->refresh();
        $this->assertSame(0, $quiz->total_points);
    }

    public function test_options_and_correct_answer_returned_as_array_not_string(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createQuiz();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/admin/quizzes/{$quiz->id}/questions/create", [
                'question_text'  => 'Question?',
                'type'           => 'radio',
                'points'         => 5,
                'options'        => ['Yes', 'No'],
                'correct_answer' => ['Yes'],
            ]);

        $response->assertCreated();
        $this->assertIsArray($response->json('data.options'));
        $this->assertIsArray($response->json('data.correct_answer'));
    }

    public function test_update_question_points_recalculates_total_points(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createQuiz();
        $question = QuizQuestion::query()->create([
            'quiz_id'        => $quiz->id,
            'question_text'  => 'Old question',
            'type'           => 'radio',
            'points'         => 10,
            'options'        => ['A', 'B'],
            'correct_answer' => ['A'],
        ]);
        $quiz->update(['total_points' => 10]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/admin/quizzes/{$quiz->id}/questions/update/{$question->id}", [
                'points' => 20,
            ])
            ->assertOk();

        $quiz->refresh();
        $this->assertSame(20, $quiz->total_points);
    }

    public function test_delete_question_with_no_answers_succeeds_and_decreases_total_points(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createQuiz();
        $question = QuizQuestion::query()->create([
            'quiz_id'        => $quiz->id,
            'question_text'  => 'Delete me',
            'type'           => 'radio',
            'points'         => 10,
            'options'        => ['A', 'B'],
            'correct_answer' => ['A'],
        ]);
        $quiz->update(['total_points' => 10]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/quizzes/{$quiz->id}/questions/delete/{$question->id}")
            ->assertOk();

        $this->assertDatabaseMissing('quiz_questions', ['id' => $question->id]);
        $quiz->refresh();
        $this->assertSame(0, $quiz->total_points);
    }

    public function test_delete_question_with_linked_answers_is_blocked(): void
    {
        $token = $this->adminToken();
        $quiz = $this->createQuiz();
        $question = QuizQuestion::query()->create([
            'quiz_id'       => $quiz->id,
            'question_text' => 'Protected question',
            'type'          => 'text',
        ]);

        $user = User::factory()->create();
        $attempt = QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'completed_at'   => now(),
        ]);
        QuizAnswer::query()->create([
            'quiz_attempt_id'  => $attempt->id,
            'quiz_question_id' => $question->id,
            'answer'           => 'Some answer',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/admin/quizzes/{$quiz->id}/questions/delete/{$question->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('quiz_questions', ['id' => $question->id]);
    }
}
