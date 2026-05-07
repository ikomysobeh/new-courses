<?php

namespace App\Http\Resources\Course;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'user_id'                 => $this->user_id,
            'course_id'               => $this->course_id,
            'course_availability_id'  => $this->course_availability_id,
            'status'                  => $this->status,
            'registered_at'           => $this->registered_at?->toIso8601String(),
            'completed_at'            => $this->completed_at?->toIso8601String(),
            'rating'                  => $this->rating,
            'feedback'                => $this->feedback,
            'course'                  => new CourseResource($this->whenLoaded('course')),
            'availability'            => new CourseAvailabilityResource($this->whenLoaded('availability')),
            'created_at'              => $this->created_at?->toIso8601String(),
            'updated_at'              => $this->updated_at?->toIso8601String(),
        ];
    }
}
