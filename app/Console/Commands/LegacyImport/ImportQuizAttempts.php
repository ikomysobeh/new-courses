<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportQuizAttempts extends LegacyImportCommand
{
    protected $signature = 'legacy:import-quiz-attempts';

    protected $description = "Import quiz_attempts. New schema adds submitted_after_deadline (NOT NULL), which doesn't exist in the old data - computed as completed_at > the quiz's old deadline (both already available), not guessed.";

    protected array $userMap = [];

    protected array $quizMap = [];

    protected array $legacyQuizDeadlines = [];

    protected function legacyTable(): string
    {
        return 'quiz_attempts';
    }

    protected function newModel(): string
    {
        return QuizAttempt::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->quizMap = Quiz::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->legacyQuizDeadlines = DB::connection('legacy')->table('quizzes')->pluck('deadline', 'id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newUserId = $this->userMap[$old['user_id']] ?? null;
        $newQuizId = $this->quizMap[$old['quiz_id']] ?? null;

        if ($newUserId === null || $newQuizId === null) {
            $this->error("Unresolved mapping for quiz_attempt legacy_id={$old['id']} (user_id={$old['user_id']}, quiz_id={$old['quiz_id']})");

            return null;
        }

        $deadline = $this->legacyQuizDeadlines[$old['quiz_id']] ?? null;
        $submittedAfterDeadline = $deadline !== null && $old['completed_at'] !== null
            && strtotime($old['completed_at']) > strtotime($deadline);

        return [
            'legacy_id' => $old['id'],
            'quiz_id' => $newQuizId,
            'user_id' => $newUserId,
            'attempt_number' => $old['attempt_number'],
            'started_at' => $old['started_at'],
            'completed_at' => $old['completed_at'],
            'score' => $old['score'],
            'manual_score' => $old['manual_score'],
            'total_score' => $old['total_score'],
            'passed' => $old['passed'],
            'submitted_after_deadline' => $submittedAfterDeadline,
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
