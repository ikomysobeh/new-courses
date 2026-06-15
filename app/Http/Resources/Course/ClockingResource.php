<?php

namespace App\Http\Resources\Course;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class ClockingResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'user'                 => $this->whenLoaded('user', function () {
                return [
                    'id'    => $this->user->id,
                    'name'  => $this->user->name,
                    'email' => $this->user->email,
                    // Add more user fields as needed
                ];
            }),
            'course_id'            => $this->course_id,
            'clock_in'             => $this->clock_in?->toIso8601String(),
            'clock_out'            => $this->clock_out?->toIso8601String(),
            'duration_in_minutes'  => $this->duration_in_minutes,
            'rating'               => $this->rating,
            'comment'              => $this->comment,
            'course'               => $this->whenLoaded('course', fn () => [
                'id'   => $this->course->id,
                'name' => $this->course->name,
            ]),
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
