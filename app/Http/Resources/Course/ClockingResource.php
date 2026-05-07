<?php

namespace App\Http\Resources\Course;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClockingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'user_id'              => $this->user_id,
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
