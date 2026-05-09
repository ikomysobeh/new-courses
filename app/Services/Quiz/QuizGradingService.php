<?php

namespace App\Services\Quiz;

use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

class QuizGradingService
{
    public function gradeAnswer(string $answer, QuizQuestion $question): array
    {
        if ($question->type === 'text') {
            return [null, null];
        }

        $correctAnswer = $question->correct_answer ?? [];

        if ($question->type === 'radio') {
            $submitted = trim($answer);
            $correct = trim($correctAnswer[0] ?? '');
            $isCorrect = $submitted === $correct;

            return [$isCorrect, $isCorrect ? (int) $question->points : 0];
        }

        if ($question->type === 'checkbox') {
            $submittedArr = json_decode($answer, true);
            if (!is_array($submittedArr)) {
                $submittedArr = [$answer];
            }
            sort($submittedArr);
            $correctArr = $correctAnswer;
            sort($correctArr);
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

                QuizAnswer::query()->create([
                    'quiz_attempt_id'  => $attempt->id,
                    'quiz_question_id' => $question->id,
                    'answer'           => $answerData['answer'],
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
        });
    }
}
