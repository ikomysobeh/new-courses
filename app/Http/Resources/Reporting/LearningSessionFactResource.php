<?php

namespace App\Http\Resources\Reporting;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class LearningSessionFactResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'session_id'            => $this->session_id,
            'user_id'               => $this->user_id,
            'user_name'             => $this->user_name ?? ($this->user?->name),
            'course_online_id'      => $this->course_online_id,
            'course_name'           => $this->course_name ?? ($this->courseOnline?->name),
            'department_id'         => $this->department_id,
            'department_name'       => $this->department_name ?? ($this->department?->name),
            'content_id'            => $this->content_id,
            'session_date'          => $this->session_date,
            'active_playback_time'  => (int)   $this->active_playback_time,
            'wall_clock_seconds'    => (int)   $this->wall_clock_seconds,
            'completion_percentage' => (float) $this->completion_percentage,
            'attention_score'       => (int)   $this->attention_score,
            'is_suspicious'         => (bool)  $this->is_suspicious,
            'content_completed'     => (bool)  $this->content_completed,
            'created_at'            => $this->created_at,
        ];
    }
}
