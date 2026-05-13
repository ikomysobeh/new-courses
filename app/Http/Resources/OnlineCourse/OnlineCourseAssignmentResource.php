<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnlineCourseAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'assigned_at'   => $this->assigned_at,
            'deadline'      => $this->deadline,
            'is_overdue'    => $this->is_overdue,
            'unassigned_at' => $this->unassigned_at,
            'user'          => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),
            'course'     => $this->whenLoaded('course', fn () => [
                'id'   => $this->course->id,
                'name' => $this->course->name,
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
