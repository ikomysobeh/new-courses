<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Quiz;
use App\Models\QuizQuestion;

class ImportQuizQuestions extends LegacyImportCommand
{
    protected $signature = 'legacy:import-quiz-questions';

    protected $description = 'Import quiz_questions. quiz_id remapped via quizzes.legacy_id. options/correct_answer decoded from old longtext into the new json columns - the old data is double-JSON-encoded (a JSON string containing a JSON string), so it needs decoding twice.';

    protected array $quizMap = [];

    protected function legacyTable(): string
    {
        return 'quiz_questions';
    }

    protected function newModel(): string
    {
        return QuizQuestion::class;
    }

    protected function beforeImport(): void
    {
        $this->quizMap = Quiz::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newQuizId = $this->quizMap[$old['quiz_id']] ?? null;

        if ($newQuizId === null) {
            $this->error("No imported Quiz for legacy quiz_id={$old['quiz_id']} (question legacy_id={$old['id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'quiz_id' => $newQuizId,
            'question_text' => $old['question_text'],
            'type' => $old['type'],
            'points' => $old['points'],
            'options' => $this->decodeDoubleJson($old['options']),
            'correct_answer' => $this->decodeDoubleJson($old['correct_answer']),
            'correct_answer_explanation' => $old['correct_answer_explanation'],
            'order' => $old['order'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }

    /**
     * The legacy column is double-JSON-encoded (a JSON string containing
     * another JSON string) - decode until we get an array, or give up and
     * return [] (e.g. for 'text'-type questions, which store no options).
     */
    protected function decodeDoubleJson(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : [];
    }
}
