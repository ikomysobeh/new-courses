<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;
use Illuminate\Support\Facades\Storage;

class OnlineCourseResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'level'              => $this->level,
            'status'             => $this->status,
            'is_active'          => $this->is_active,
            'estimated_duration' => $this->estimated_duration,
            'deadline'           => $this->deadline,
            'image_path'         => $this->image_path
                ? Storage::disk('public')->url($this->image_path)
                : null,
            'modules_count'      => $this->whenCounted('modules'),
            'enrollments_count'  => $this->whenCounted('assignments'),
            'creator'            => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
