<?php

namespace App\Services\Quiz;

use App\Models\QuizQuestion;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizQuestionService
{
    public function addQuestion(int $quizId, array $data, bool $recompute = true): QuizQuestion
    {
        return DB::transaction(function () use ($quizId, $data, $recompute) {
            $question = QuizQuestion::query()->create([
                'quiz_id'                    => $quizId,
                'question_text'              => $data['question_text'],
                'type'                       => $data['type'],
                'points'                     => $data['points'] ?? null,
                'options'                    => isset($data['options']) ? $data['options'] : null,
                'correct_answer'             => isset($data['correct_answer']) ? $data['correct_answer'] : null,
                'correct_answer_explanation' => $data['correct_answer_explanation'] ?? null,
                'order'                      => $data['order'] ?? 0,
            ]);

            if ($recompute) {
                $this->recalculateTotalPoints($quizId);
            }

            return $question;
        });
    }

    public function updateQuestion(QuizQuestion $question, array $data): QuizQuestion
    {
        return DB::transaction(function () use ($question, $data) {
            $pointsOrTypeChanged = isset($data['points']) || isset($data['type']);

            $updateData = [];

            if (isset($data['question_text'])) {
                $updateData['question_text'] = $data['question_text'];
            }
            if (isset($data['type'])) {
                $updateData['type'] = $data['type'];
            }
            if (array_key_exists('points', $data)) {
                $updateData['points'] = $data['points'];
            }
            if (array_key_exists('options', $data)) {
                $updateData['options'] = $data['options'];
            }
            if (array_key_exists('correct_answer', $data)) {
                $updateData['correct_answer'] = $data['correct_answer'];
            }
            if (array_key_exists('correct_answer_explanation', $data)) {
                $updateData['correct_answer_explanation'] = $data['correct_answer_explanation'];
            }
            if (isset($data['order'])) {
                $updateData['order'] = $data['order'];
            }

            $question->update($updateData);

            if ($pointsOrTypeChanged) {
                $this->recalculateTotalPoints($question->quiz_id);
            }

            return $question->fresh();
        });
    }

    public function deleteQuestion(QuizQuestion $question): void
    {
        $hasAnswers = $question->answers()->exists();

        if ($hasAnswers) {
            throw ValidationException::withMessages([
                'quiz_question_id' => ['Cannot delete a question that has user answers linked to it.'],
            ]);
        }

        $quizId = $question->quiz_id;
        $question->delete();

        $this->recalculateTotalPoints($quizId);
    }

    public function recalculateTotalPoints(int $quizId): void
    {
        $total = QuizQuestion::query()
            ->where('quiz_id', $quizId)
            ->whereIn('type', ['radio', 'checkbox'])
            ->sum('points');

        Quiz::query()->where('id', $quizId)->update(['total_points' => (int) $total]);
    }
}
