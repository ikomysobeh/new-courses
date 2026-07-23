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
            'max_attempts'         => $this->max_attempts,
            // How many attempts this user has made on this quiz.
            'attempts_count'       => isset($this->attempts_count)
                ? (int) $this->attempts_count
                : QuizAttempt::query()
                    ->where('quiz_id', $this->id)
                    ->where('user_id', $request->user()->id)
                    ->count(),
            // Latest attempt summary (drives the status badge on the list cards).
            'last_attempt'         => $this->relationLoaded('attempts')
                ? (function () {
                    $a = $this->attempts->first(); // ordered newest-first
                    if (!$a) {
                        return null;
                    }
                    $completed = $a->completed_at !== null;
                    return [
                        'id'           => $a->id,
                        'started_at'   => $a->started_at,
                        'submitted_at' => $a->completed_at,
                        'score'        => $a->score,
                        'total_score'  => $a->total_score,
                        'total_points' => $this->total_points,
                        'passed'       => $completed ? (bool) $a->passed : null,
                    ];
                })()
                : null,
            // True when the user's latest completed attempt still has open-text
            // answers awaiting manual grading — result is not final ("Under Review").
            'user_result_pending'  => (function () use ($request) {
                // Preloaded via getAllForUser (withExists) — the list path.
                if (isset($this->user_result_pending)) {
                    return (bool) $this->user_result_pending;
                }
                // Detail path: compute for the latest completed attempt.
                if (!$this->relationLoaded('questions')) {
                    return false;
                }
                $attempt = QuizAttempt::query()
                    ->where('quiz_id', $this->id)
                    ->where('user_id', $request->user()->id)
                    ->whereNotNull('completed_at')
                    ->latest('id')
                    ->first();
                if (!$attempt) {
                    return false;
                }
                return $attempt->answers()
                    ->whereNull('is_correct')
                    ->whereHas('question', fn ($q) => $q->where('type', 'text'))
                    ->exists();
            })(),
            'questions'            => $this->whenLoaded(
                'questions',
                fn () => UserQuizQuestionResource::collection($this->questions)
            ),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
