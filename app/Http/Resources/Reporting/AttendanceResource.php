<?php

namespace App\Http\Resources\Reporting;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class AttendanceResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'user_id'             => $this->user_id,
            'user_name'           => $this->user_name,
            'user_email'          => $this->user_email,
            'department_id'       => $this->department_id,
            'department_name'     => $this->department_name,
            'course_id'           => $this->course_id,
            'course_name'         => $this->course_name ?? 'General Attendance',
            'clock_in'            => $this->clock_in,
            'clock_out'           => $this->clock_out,
            'duration_in_minutes' => $this->duration_in_minutes !== null ? (int) $this->duration_in_minutes : null,
            'rating'              => $this->rating !== null ? (int) $this->rating : null,
            'comment'             => $this->comment,
        ];
    }
}
