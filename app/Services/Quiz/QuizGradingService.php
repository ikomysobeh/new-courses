<?php

namespace App\Services\Quiz;

use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Services\OnlineCourse\User\ContentProgressService;
use Illuminate\Support\Facades\DB;

class QuizGradingService
{
    public function gradeAnswer(mixed $answer, QuizQuestion $question): array
    {
        if ($question->type === 'text') {
            return [null, null];
        }

        $correctAnswer = $question->correct_answer ?? [];

        if ($question->type === 'radio') {
            $submitted = trim((string) $answer);
            $correct = trim($correctAnswer[0] ?? '');
            $isCorrect = $submitted === $correct;

            return [$isCorrect, $isCorrect ? (int) $question->points : 0];
        }

        if ($question->type === 'checkbox') {
            $submittedArr = $this->normalizeCheckboxSelections($answer);
            $correctArr = $this->normalizeCheckboxSelections($correctAnswer);
            $isCorrect = $submittedArr === $correctArr;

            return [$isCorrect, $isCorrect ? (int) $question->points : 0];
        }

        return [null, null];
    }

    public function autoGradeAttempt(QuizAttempt $attempt, array $answers): void
    {
        DB::transaction(function () use ($attempt, $answers) {
            foreach ($answers as $answerData) {
                $question = QuizQuestion::query()->findOrFail($answerData['quiz_question_id']);
                [$isCorrect, $pointsEarned] = $this->gradeAnswer($answerData['answer'], $question);
                $rawAnswer = $answerData['answer'];

                QuizAnswer::query()->create([
                    'quiz_attempt_id'  => $attempt->id,
                    'quiz_question_id' => $question->id,
                    'answer'           => is_array($rawAnswer)
                        ? json_encode($rawAnswer)
                        : (string) $rawAnswer,
                    'is_correct'       => $isCorrect,
                    'points_earned'    => $pointsEarned,
                ]);
            }

            $this->finalizeScore($attempt->id);
        });
    }

    public function manualGradeAnswer(int $answerId, int $pointsEarned): void
    {
        DB::transaction(function () use ($answerId, $pointsEarned) {
            $answer = QuizAnswer::query()->findOrFail($answerId);
            $answer->update([
                'points_earned' => $pointsEarned,
                'is_correct'    => $pointsEarned > 0,
            ]);

            $this->finalizeScore($answer->quiz_attempt_id);
        });
    }

    public function finalizeScore(int $attemptId): void
    {
        DB::transaction(function () use ($attemptId) {
            $attempt = QuizAttempt::query()
                ->with(['quiz', 'answers.question'])
                ->findOrFail($attemptId);

            $autoScore = $attempt->answers
                ->filter(fn ($a) => $a->question && $a->question->type !== 'text')
                ->sum('points_earned');

            $manualScore = $attempt->answers
                ->filter(fn ($a) => $a->question && $a->question->type === 'text')
                ->sum('points_earned');

            $totalScore = $autoScore + $manualScore;
            $threshold = ($attempt->quiz->pass_threshold / 100) * $attempt->quiz->total_points;
            $passed = $totalScore >= $threshold;

            $attempt->update([
                'score'        => (int) $autoScore,
                'manual_score' => (int) $manualScore,
                'total_score'  => (int) $totalScore,
                'passed'       => $passed,
            ]);

            // A required quiz gates course completion, but course status is only
            // ever recomputed on content progress. Without this, a user who
            // finishes all content and passes the quiz afterwards stays stuck at
            // 'in_progress' forever. Runs on fail too, so a manual re-grade below
            // the threshold can take a course back out of 'completed'.
            if ($attempt->quiz->course_online_id) {
                app(ContentProgressService::class)->recalculateCourseProgress(
                    $attempt->user_id,
                    $attempt->quiz->course_online_id,
                    touchLastAccessed: false,
                );
            }
        });
    }

    private function normalizeCheckboxSelections(mixed $value): array
    {
        $items = [];

        if (is_array($value)) {
            $items = $value;
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $items = $decoded;
            } elseif (str_contains($value, ',')) {
                $items = array_map('trim', explode(',', $value));
            } else {
                $items = [trim($value)];
            }
        }

        $normalized = array_map(
            static fn ($item) => trim((string) $item),
            $items
        );

        $normalized = array_values(array_filter(
            $normalized,
            static fn ($item) => $item !== ''
        ));

        $normalized = array_values(array_unique($normalized));
        sort($normalized, SORT_NATURAL | SORT_FLAG_CASE);

        return $normalized;
    }
}