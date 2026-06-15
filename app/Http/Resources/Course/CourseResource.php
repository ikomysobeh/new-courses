<?php

namespace App\Http\Resources\Course;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;
use Illuminate\Support\Facades\Storage;

class CourseResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'description'        => $this->description,
            'image_path'         => $this->image_path
                ? Storage::disk('public')->url($this->image_path)
                : null,
            'level'              => $this->level,
            'duration'           => $this->duration,
            'status'             => $this->status,
            'privacy'            => $this->privacy,
            'created_by'         => $this->created_by,
            'registrations_count'=> $this->whenCounted('registrations'),
            'availabilities'     => CourseAvailabilityResource::collection(
                $this->whenLoaded('availabilities')
            ),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
