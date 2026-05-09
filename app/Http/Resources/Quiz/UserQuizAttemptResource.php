<?php

namespace App\Http\Resources\Quiz;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserQuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'quiz_id'        => $this->quiz_id,
            'attempt_number' => $this->attempt_number,
            'started_at'     => $this->started_at,
            'completed_at'   => $this->completed_at,
            'score'          => $this->score,
            'manual_score'   => $this->manual_score,
            'total_score'    => $this->total_score,
            'passed'         => (bool) $this->passed,
            'answers'        => $this->whenLoaded('answers', function () {
                $includeIsCorrect = $this->resolveIncludeIsCorrect();

                return $this->answers->map(function ($answer) use ($includeIsCorrect) {
                    $resource = new UserQuizAnswerResource($answer);
                    $resource->includeIsCorrect = $includeIsCorrect;

                    return $resource;
                });
            }),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }

    private function resolveIncludeIsCorrect(): bool
    {
        if (!$this->relationLoaded('quiz') || !$this->quiz) {
            return false;
        }

        $quiz = $this->quiz;

        return match ($quiz->show_correct_answers) {
            'always'             => true,
            'after_pass'         => (bool) $this->passed,
            'after_max_attempts' => QuizAttempt::query()
                ->where('quiz_id', $this->quiz_id)
                ->where('user_id', $this->user_id)
                ->count() >= $quiz->max_attempts,
            default              => false,
        };
    }
}
