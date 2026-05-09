<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserQuizResultApiTest extends TestCase
{
    use RefreshDatabase;

    private function setup_quiz_with_attempt(string $showCorrectAnswers, bool $passed, int $attemptCount = 1): array
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@newproject.test')->firstOrFail();
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $quiz = Quiz::query()->create([
            'title'                => 'Result Test Quiz',
            'course_id'            => $course->id,
            'status'               => 'published',
            'max_attempts'         => 3,
            'total_points'         => 10,
            'pass_threshold'       => 80.00,
            'show_correct_answers' => $showCorrectAnswers,
        ]);

        QuizAssignment::query()->create([
            'quiz_id'     => $quiz->id,
            'user_id'     => $user->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id'        => $quiz->id,
            'question_text'  => 'What is 2+2?',
            'type'           => 'radio',
            'points'         => 10,
            'options'        => ['3', '4'],
            'correct_answer' => ['4'],
        ]);

        $attempt = QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => $attemptCount,
            'completed_at'   => now(),
            'score'          => $passed ? 10 : 0,
            'total_score'    => $passed ? 10 : 0,
            'passed'         => $passed,
        ]);

        $answer = QuizAnswer::query()->create([
            'quiz_attempt_id'  => $attempt->id,
            'quiz_question_id' => $question->id,
            'answer'           => $passed ? '4' : '3',
            'is_correct'       => $passed ? 1 : 0,
            'points_earned'    => $passed ? 10 : 0,
        ]);

        return compact('user', 'quiz', 'attempt', 'answer');
    }

    public function test_show_correct_answers_never_omits_is_correct(): void
    {
        ['user' => $user, 'quiz' => $quiz, 'attempt' => $attempt] =
            $this->setup_quiz_with_attempt('never', true);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('t')->plainTextToken)
            ->getJson("/api/user/quizzes/{$quiz->id}/result/{$attempt->id}");

        $response->assertOk();
        foreach ($response->json('data.answers') as $ans) {
            $this->assertArrayNotHasKey('is_correct', $ans);
        }
    }

    public function test_show_correct_answers_after_pass_included_when_passed(): void
    {
        ['user' => $user, 'quiz' => $quiz, 'attempt' => $attempt] =
            $this->setup_quiz_with_attempt('after_pass', true);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('t')->plainTextToken)
            ->getJson("/api/user/quizzes/{$quiz->id}/result/{$attempt->id}");

        $response->assertOk();
        foreach ($response->json('data.answers') as $ans) {
            $this->assertArrayHasKey('is_correct', $ans);
        }
    }

    public function test_show_correct_answers_after_pass_omitted_when_not_passed(): void
    {
        ['user' => $user, 'quiz' => $quiz, 'attempt' => $attempt] =
            $this->setup_quiz_with_attempt('after_pass', false);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('t')->plainTextToken)
            ->getJson("/api/user/quizzes/{$quiz->id}/result/{$attempt->id}");

        $response->assertOk();
        foreach ($response->json('data.answers') as $ans) {
            $this->assertArrayNotHasKey('is_correct', $ans);
        }
    }

    public function test_show_correct_answers_after_max_attempts_included_when_at_max(): void
    {
        // Create quiz with max_attempts = 1, so 1 completed attempt = at max
        ['user' => $user, 'quiz' => $quiz, 'attempt' => $attempt] =
            $this->setup_quiz_with_attempt('after_max_attempts', false, 1);

        $quiz->update(['max_attempts' => 1]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('t')->plainTextToken)
            ->getJson("/api/user/quizzes/{$quiz->id}/result/{$attempt->id}");

        $response->assertOk();
        foreach ($response->json('data.answers') as $ans) {
            $this->assertArrayHasKey('is_correct', $ans);
        }
    }

    public function test_show_correct_answers_always_includes_is_correct(): void
    {
        ['user' => $user, 'quiz' => $quiz, 'attempt' => $attempt] =
            $this->setup_quiz_with_attempt('always', false);

        $response = $this->withHeader('Authorization', 'Bearer ' . $user->createToken('t')->plainTextToken)
            ->getJson("/api/user/quizzes/{$quiz->id}/result/{$attempt->id}");

        $response->assertOk();
        foreach ($response->json('data.answers') as $ans) {
            $this->assertArrayHasKey('is_correct', $ans);
        }
    }
}
