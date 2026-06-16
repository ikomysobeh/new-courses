<?php

namespace App\Services\Quiz;

use App\Exceptions\Quiz\AlreadyPassedException;
use App\Exceptions\Quiz\DeadlinePassedException;
use App\Exceptions\Quiz\MaxAttemptsReachedException;
use App\Exceptions\Quiz\QuizNotAssignedException;
use App\Exceptions\Quiz\QuizNotAvailableException;
use App\Exceptions\Quiz\RetryDelayActiveException;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    public function __construct(
        private readonly QuizGradingService $gradingService
    ) {}

    public function canUserAttempt(int $userId, int $quizId): true
    {
        $quiz = Quiz::query()->find($quizId);

        if (!$quiz || $quiz->status !== 'published') {
            throw new QuizNotAvailableException();
        }

        // Check if user is assigned directly to the quiz
        $isAssigned = $quiz->assignments()->where('user_id', $userId)->exists();

        // Course-quiz access: enrollment in the course this quiz belongs to is enough.
        // Resolve the course id from the MODULE relationship first (reliable, since
        // the quiz's own course_online_id column is frequently NULL), then fall back
        // to the quiz column. Covers both module quizzes AND course-level quizzes.
        $courseOnlineId = $quiz->module?->course_online_id ?? $quiz->course_online_id;

        $isCourseAssigned = false;
        if ($courseOnlineId) {
            $isCourseAssigned = \App\Models\CourseOnlineAssignment::query()
                ->where('user_id', $userId)
                ->where('course_online_id', $courseOnlineId)
                ->exists();
        }

        if (!$isAssigned && !$isCourseAssigned) {
            throw new QuizNotAssignedException();
        }

        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->orderByDesc('attempt_number')
            ->get();

        $hasPassed = $attempts->contains('passed', true);
        if ($hasPassed) {
            throw new AlreadyPassedException();
        }

        if ($attempts->count() >= $quiz->max_attempts) {
            throw new MaxAttemptsReachedException();
        }

        $lastAttempt = $attempts->first();
        if ($lastAttempt && $quiz->retry_delay_hours > 0 && $lastAttempt->completed_at) {
            $retryAt = $lastAttempt->completed_at->addHours($quiz->retry_delay_hours);
            if (Carbon::now()->lt($retryAt)) {
                throw new RetryDelayActiveException($retryAt->toDateTimeString());
            }
        }

        if ($quiz->deadline && Carbon::now()->gt($quiz->deadline)) {
            throw new DeadlinePassedException();
        }

        return true;
    }

    public function startAttempt(int $userId, int $quizId): QuizAttempt
    {
        $this->canUserAttempt($userId, $quizId);

        return DB::transaction(function () use ($userId, $quizId) {
            $maxAttemptNumber = QuizAttempt::query()
                ->where('quiz_id', $quizId)
                ->where('user_id', $userId)
                ->max('attempt_number') ?? 0;

            return QuizAttempt::query()->create([
                'quiz_id'        => $quizId,
                'user_id'        => $userId,
                'attempt_number' => $maxAttemptNumber + 1,
                'started_at'     => Carbon::now(),
            ]);
        });
    }

    public function submitAttempt(int $attemptId, int $userId, array $answers): QuizAttempt
    {
        return DB::transaction(function () use ($attemptId, $userId, $answers) {
            $attempt = QuizAttempt::query()
                ->where('id', $attemptId)
                ->where('user_id', $userId)
                ->firstOrFail();

            if ($attempt->completed_at !== null) {
                throw ValidationException::withMessages([
                    'attempt_id' => ['This attempt has already been submitted.'],
                ]);
            }

            $quiz = $attempt->quiz;

            $submittedAfterDeadline = $quiz->deadline && Carbon::now()->gt($quiz->deadline);

            $attempt->update([
                'submitted_after_deadline' => $submittedAfterDeadline,
            ]);

            $this->gradingService->autoGradeAttempt($attempt, $answers);

            $attempt->update(['completed_at' => Carbon::now()]);

            return $attempt->fresh(['answers.question', 'quiz']);
        });
    }

    public function getAttemptResult(int $attemptId, int $userId): QuizAttempt
    {
        return QuizAttempt::query()
            ->with(['answers.question', 'quiz'])
            ->where('id', $attemptId)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    public function getAdminAttemptCards(int $quizId): array
    {
        $total  = QuizAttempt::query()->where('quiz_id', $quizId)->count();
        $passed = QuizAttempt::query()->where('quiz_id', $quizId)->where('passed', true)->count();
        $failed = QuizAttempt::query()->where('quiz_id', $quizId)->whereNotNull('completed_at')->where('passed', false)->count();
        $avg    = (int) round((float) (QuizAttempt::query()->where('quiz_id', $quizId)->whereNotNull('completed_at')->avg('total_score') ?? 0));

        return [
            ['key' => 'total_attempts',  'title' => 'Total Attempts',  'value' => $total],
            ['key' => 'passed_attempts', 'title' => 'Passed Attempts', 'value' => $passed],
            ['key' => 'failed_attempts', 'title' => 'Failed Attempts', 'value' => $failed],
            ['key' => 'average_score',   'title' => 'Average Score',   'value' => $avg],
        ];
    }
}
