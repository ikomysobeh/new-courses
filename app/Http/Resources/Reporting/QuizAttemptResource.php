<?php

namespace App\Http\Resources\Reporting;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class QuizAttemptResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $achieved   = $this->total_score ?? $this->score;
        $maxPoints  = (int) $this->total_points;
        $percentage = $maxPoints > 0 ? round($achieved / $maxPoints * 100, 2) : null;

        if ($this->completed_at === null) {
            $status = 'pending';
        } else {
            $status = $this->passed ? 'passed' : 'failed';
        }

        return [
            'id'                       => $this->id,
            'user_id'                  => $this->user_id,
            'user_name'                => $this->user_name,
            'user_email'               => $this->user_email,
            'department_id'            => $this->department_id,
            'department_name'          => $this->department_name,
            'quiz_id'                  => $this->quiz_id,
            'quiz_title'               => $this->quiz_title,
            'attempt_number'           => (int) $this->attempt_number,
            'score'                    => (int) $this->score,
            'total_score'              => $this->total_score !== null ? (int) $this->total_score : null,
            'total_points'             => $maxPoints,
            'percentage'               => $percentage,
            'pass_threshold'           => (float) $this->pass_threshold,
            'passed'                   => (bool) $this->passed,
            'status'                   => $status,
            'submitted_after_deadline' => (bool) $this->submitted_after_deadline,
            'started_at'               => $this->started_at,
            'completed_at'             => $this->completed_at,
        ];
    }
}
