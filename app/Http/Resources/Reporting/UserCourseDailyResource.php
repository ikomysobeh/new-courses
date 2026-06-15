<?php

namespace App\Http\Resources\Reporting;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class UserCourseDailyResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'user_id'                 => $this->user_id,
            'user_name'               => $this->user_name ?? ($this->user?->name),
            'course_online_id'        => $this->course_online_id,
            'course_name'             => $this->course_name ?? ($this->courseOnline?->name),
            'department_id'           => $this->department_id,
            'department_name'         => $this->department_name ?? ($this->department?->name),
            'report_date'             => $this->report_date,
            'sessions_count'          => (int) $this->sessions_count,
            'active_playback_time'    => (int) $this->active_playback_time,
            'content_items_completed' => (int) $this->content_items_completed,
            'course_progress_pct'     => (float) $this->course_progress_pct,
            'updated_at'              => $this->updated_at,
        ];
    }
}
