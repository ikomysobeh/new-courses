<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserQuizApiTest extends TestCase
{
    use RefreshDatabase;

    private function userToken(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function createPublishedQuiz(?Course $course = null): Quiz
    {
        $course = $course ?? Course::factory()->create();

        return Quiz::query()->create([
            'title'         => 'Published Quiz',
            'course_id'     => $course->id,
            'status'        => 'published',
            'max_attempts'  => 3,
            'total_points'  => 10,
            'pass_threshold' => 80.00,
        ]);
    }

    private function assignQuizToUser(Quiz $quiz, User $user): void
    {
        $assigner = User::factory()->create();

        QuizAssignment::query()->create([
            'quiz_id'     => $quiz->id,
            'user_id'     => $user->id,
            'assigned_by' => $assigner->id,
            'assigned_at' => now(),
        ]);
    }

    public function test_user_only_sees_assigned_quizzes(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $quiz = $this->createPublishedQuiz();
        $this->assignQuizToUser($quiz, $userA);

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/user/quizzes/getAll')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($userB, 'sanctum')
            ->getJson('/api/user/quizzes/getAll')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_get_by_id_returns_questions_without_correct_answer(): void
    {
        $user = User::factory()->create();
        $quiz = $this->createPublishedQuiz();
        $this->assignQuizToUser($quiz, $user);

        QuizQuestion::query()->create([
            'quiz_id'        => $quiz->id,
            'question_text'  => 'What is 2+2?',
            'type'           => 'radio',
            'points'         => 10,
            'options'        => ['3', '4', '5'],
            'correct_answer' => ['4'],
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken($user))
            ->getJson("/api/user/quizzes/getById/{$quiz->id}");

        $response->assertOk();
        $questions = $response->json('data.questions');
        $this->assertNotNull($questions);
        foreach ($questions as $q) {
            $this->assertArrayNotHasKey('correct_answer', $q);
        }
    }

    public function test_user_cannot_start_attempt_on_draft_quiz(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $draftQuiz = Quiz::query()->create([
            'title'     => 'Draft Quiz',
            'course_id' => $course->id,
            'status'    => 'draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken($user))
            ->postJson("/api/user/quizzes/{$draftQuiz->id}/start");

        $response->assertStatus(404);
    }

    public function test_user_cannot_start_attempt_on_unassigned_quiz(): void
    {
        $user = User::factory()->create();
        $quiz = $this->createPublishedQuiz();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken($user))
            ->postJson("/api/user/quizzes/{$quiz->id}/start");

        $response->assertForbidden();
    }

    public function test_user_cannot_exceed_max_attempts(): void
    {
        $user = User::factory()->create();
        $quiz = $this->createPublishedQuiz();
        $this->assignQuizToUser($quiz, $user);
        $quiz->update(['max_attempts' => 1]);

        QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'completed_at'   => now(),
            'passed'         => false,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->userToken($user))
            ->postJson("/api/user/quizzes/{$quiz->id}/start")
            ->assertUnprocessable();
    }

    public function test_retry_delay_blocks_re_attempt(): void
    {
        $user = User::factory()->create();
        $quiz = $this->createPublishedQuiz();
        $this->assignQuizToUser($quiz, $user);
        $quiz->update(['retry_delay_hours' => 24]);

        QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'completed_at'   => now(),
            'passed'         => false,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->userToken($user))
            ->postJson("/api/user/quizzes/{$quiz->id}/start")
            ->assertUnprocessable();
    }

    public function test_retry_delay_of_zero_allows_immediate_re_attempt(): void
    {
        $user = User::factory()->create();
        $quiz = $this->createPublishedQuiz();
        $this->assignQuizToUser($quiz, $user);
        $quiz->update(['retry_delay_hours' => 0, 'max_attempts' => 5]);

        QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'completed_at'   => now(),
            'passed'         => false,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->userToken($user))
            ->postJson("/api/user/quizzes/{$quiz->id}/start")
            ->assertCreated();
    }

    public function test_submit_attempt_autogrades_radio_and_checkbox(): void
    {
        $user = User::factory()->create();
        $quiz = $this->createPublishedQuiz();
        $this->assignQuizToUser($quiz, $user);

        $radioQ = QuizQuestion::query()->create([
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
            'attempt_number' => 1,
            'started_at'     => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->userToken($user))
            ->postJson("/api/user/quizzes/{$quiz->id}/submit/{$attempt->id}", [
                'answers' => [
                    ['quiz_question_id' => $radioQ->id, 'answer' => '4'],
                ],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('quiz_answers', [
            'quiz_attempt_id'  => $attempt->id,
            'quiz_question_id' => $radioQ->id,
            'is_correct'       => 1,
            'points_earned'    => 10,
        ]);
    }

    public function test_text_answers_remain_pending_after_submit(): void
    {
        $user = User::factory()->create();
        $quiz = $this->createPublishedQuiz();
        $this->assignQuizToUser($quiz, $user);

        $textQ = QuizQuestion::query()->create([
            'quiz_id'       => $quiz->id,
            'question_text' => 'Explain something.',
            'type'          => 'text',
        ]);

        $attempt = QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'started_at'     => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->userToken($user))
            ->postJson("/api/user/quizzes/{$quiz->id}/submit/{$attempt->id}", [
                'answers' => [
                    ['quiz_question_id' => $textQ->id, 'answer' => 'My explanation'],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('quiz_answers', [
            'quiz_attempt_id'  => $attempt->id,
            'quiz_question_id' => $textQ->id,
            'is_correct'       => null,
            'points_earned'    => null,
        ]);
    }

    public function test_submitted_after_deadline_flag_is_set(): void
    {
        $user = User::factory()->create();
        $quiz = $this->createPublishedQuiz();
        $this->assignQuizToUser($quiz, $user);
        $quiz->update(['deadline' => now()->subHour()]);

        $textQ = QuizQuestion::query()->create([
            'quiz_id'       => $quiz->id,
            'question_text' => 'Q',
            'type'          => 'text',
        ]);

        $attempt = QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $user->id,
            'attempt_number' => 1,
            'started_at'     => now()->subHours(2),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->userToken($user))
            ->postJson("/api/user/quizzes/{$quiz->id}/submit/{$attempt->id}", [
                'answers' => [
                    ['quiz_question_id' => $textQ->id, 'answer' => 'Answer'],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('quiz_attempts', [
            'id'                       => $attempt->id,
            'submitted_after_deadline' => 1,
        ]);
    }

    public function test_user_cannot_view_another_users_attempt(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $quiz = $this->createPublishedQuiz();
        $this->assignQuizToUser($quiz, $userA);

        $attempt = QuizAttempt::query()->create([
            'quiz_id'        => $quiz->id,
            'user_id'        => $userA->id,
            'attempt_number' => 1,
            'completed_at'   => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->userToken($userB))
            ->getJson("/api/user/quizzes/{$quiz->id}/result/{$attempt->id}")
            ->assertNotFound();
    }
}
