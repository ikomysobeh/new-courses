<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;

class ImportQuizAnswers extends LegacyImportCommand
{
    protected $signature = 'legacy:import-quiz-answers';

    protected $description = 'Import quiz_answers - near 1:1, remaps quiz_attempt_id and quiz_question_id.';

    protected array $attemptMap = [];

    protected array $questionMap = [];

    protected function legacyTable(): string
    {
        return 'quiz_answers';
    }

    protected function newModel(): string
    {
        return QuizAnswer::class;
    }

    protected function beforeImport(): void
    {
        $this->attemptMap = QuizAttempt::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->questionMap = QuizQuestion::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newAttemptId = $this->attemptMap[$old['quiz_attempt_id']] ?? null;
        $newQuestionId = $this->questionMap[$old['quiz_question_id']] ?? null;

        if ($newAttemptId === null || $newQuestionId === null) {
            $this->error("Unresolved mapping for quiz_answer legacy_id={$old['id']} (quiz_attempt_id={$old['quiz_attempt_id']}, quiz_question_id={$old['quiz_question_id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'quiz_attempt_id' => $newAttemptId,
            'quiz_question_id' => $newQuestionId,
            'answer' => $old['answer'],
            'is_correct' => $old['is_correct'],
            'points_earned' => $old['points_earned'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
