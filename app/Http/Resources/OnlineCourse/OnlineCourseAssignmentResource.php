<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Resources\BaseResource;

class OnlineCourseAssignmentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $deadline = $this->course?->deadline;
        $isOverdue = $deadline !== null
            && Carbon::parse($deadline)->isPast()
            && !((int) ($this->course_completed ?? 0));

        return [
            'id'          => $this->id,
            'assigned_at' => $this->assigned_at,
            'deadline'    => $deadline,
            'is_overdue'  => $isOverdue,
            'user'        => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),
            'course'     => $this->whenLoaded('course', fn () => [
                'id'       => $this->course->id,
                'name'     => $this->course->name,
                'deadline' => $this->course->deadline,
            ]),
            'assigned_by' => $this->whenLoaded('assignedBy', fn () =>
                $this->assignedBy ? [
                    'id'   => $this->assignedBy->id,
                    'name' => $this->assignedBy->name,
                ] : null
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
