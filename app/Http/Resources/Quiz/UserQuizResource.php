<?php

namespace App\Http\Resources\Quiz;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class UserQuizResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'description'          => $this->description,
            'pass_threshold'       => $this->pass_threshold,
            'total_points'         => $this->total_points,
            'time_limit_minutes'   => $this->time_limit_minutes,
            'deadline'             => $this->deadline,
            'show_correct_answers' => $this->show_correct_answers,
            'user_passed'          => (function () use ($request) {
                // Pre-loaded via getAllForUser (withExists)
                if (isset($this->user_has_attempted)) {
                    if (!(bool) $this->user_has_attempted) return null;
                    return (bool) $this->user_passed;
                }
                // Fallback: live query
                $hasAttempt = QuizAttempt::query()
                    ->where('quiz_id', $this->id)
                    ->where('user_id', $request->user()->id)
                    ->whereNotNull('completed_at')
                    ->exists();
                if (!$hasAttempt) return null;
                return (bool) QuizAttempt::query()
                    ->where('quiz_id', $this->id)
                    ->where('user_id', $request->user()->id)
                    ->where('passed', true)
                    ->exists();
            })(),
            'user_total_score'     => isset($this->user_total_score)
                ? (int) $this->user_total_score
                : (int) QuizAttempt::query()
                    ->where('quiz_id', $this->id)
                    ->where('user_id', $request->user()->id)
                    ->whereNotNull('completed_at')
                    ->max('total_score'),
            'questions'            => $this->whenLoaded(
                'questions',
                fn () => UserQuizQuestionResource::collection($this->questions)
            ),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
