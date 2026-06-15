<?php

namespace App\Http\Resources\Course;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class CourseAssignmentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'course_id'               => $this->course_id,
            'user_id'                 => $this->user_id,
            'assigned_by'             => $this->assigned_by,
            'course_availability_id'  => $this->course_availability_id,
            'assigned_at'             => $this->assigned_at?->toIso8601String(),
            'course'                  => new CourseResource($this->whenLoaded('course')),
            'user'                    => $this->whenLoaded('user', fn () => [
                'id'             => $this->user->id,
                'name'           => $this->user->name,
                'email'          => $this->user->email,
                'link_expires_at' => $this->user->login_token_expires_at?->toIso8601String(),
            ]),
            'assigned_by_user'        => $this->whenLoaded('assignedBy', fn () => [
                'id'   => $this->assignedBy->id,
                'name' => $this->assignedBy->name,
            ]),
            'created_at'              => $this->created_at?->toIso8601String(),
            'updated_at'              => $this->updated_at?->toIso8601String(),
        ];
    }
}
