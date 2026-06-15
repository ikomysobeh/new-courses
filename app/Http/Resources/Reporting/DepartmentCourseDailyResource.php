<?php

namespace App\Http\Resources\Reporting;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class DepartmentCourseDailyResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'department_id'           => $this->department_id,
            'department_name'         => $this->department_name ?? ($this->department?->name),
            'course_online_id'        => $this->course_online_id,
            'course_name'             => $this->course_name ?? ($this->courseOnline?->name),
            'report_date'             => $this->report_date,
            'enrolled_users'          => (int) $this->enrolled_users,
            'active_users'            => (int) $this->active_users,
            'completed_users'         => (int) $this->completed_users,
            'avg_progress_percentage' => (float) $this->avg_progress_percentage,
            'total_active_seconds'    => (int) $this->total_active_seconds,
            'updated_at'              => $this->updated_at,
        ];
    }
}
