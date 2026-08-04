<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\User;

class ImportQuizAssignments extends LegacyImportCommand
{
    protected $signature = 'legacy:import-quiz-assignments';

    protected $description = 'Import quiz_assignments - identical schema, just remaps user_id/quiz_id/assigned_by.';

    protected array $userMap = [];

    protected array $quizMap = [];

    protected function legacyTable(): string
    {
        return 'quiz_assignments';
    }

    protected function newModel(): string
    {
        return QuizAssignment::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->quizMap = Quiz::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newUserId = $this->userMap[$old['user_id']] ?? null;
        $newQuizId = $this->quizMap[$old['quiz_id']] ?? null;
        $newAssignedBy = $this->userMap[$old['assigned_by']] ?? null;

        if ($newUserId === null || $newQuizId === null || $newAssignedBy === null) {
            $this->error("Unresolved mapping for quiz_assignment legacy_id={$old['id']} (user_id={$old['user_id']}, quiz_id={$old['quiz_id']}, assigned_by={$old['assigned_by']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'user_id' => $newUserId,
            'quiz_id' => $newQuizId,
            'assigned_by' => $newAssignedBy,
            'assigned_at' => $old['assigned_at'],
            'notification_sent' => $old['notification_sent'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
