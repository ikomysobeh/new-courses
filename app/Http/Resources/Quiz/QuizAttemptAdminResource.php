<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class QuizAttemptAdminResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'quiz_id'                  => $this->quiz_id,
            'user_id'                  => $this->user_id,
            'attempt_number'           => $this->attempt_number,
            'started_at'               => $this->started_at,
            'completed_at'             => $this->completed_at,
            'score'                    => $this->score,
            'manual_score'             => $this->manual_score,
            'total_score'              => $this->total_score,
            'passed'                   => (bool) $this->passed,
            'submitted_after_deadline' => (bool) $this->submitted_after_deadline,
            'user'                     => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),
            'answers'                  => $this->whenLoaded(
                'answers',
                fn () => QuizAnswerAdminResource::collection($this->answers)
            ),
            'created_at'               => $this->created_at,
            'updated_at'               => $this->updated_at,
        ];
    }
}
