<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;
use Illuminate\Support\Facades\Storage;

class OnlineCourseDetailResource extends BaseResource
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
            'estimated_duration' => $this->estimated_duration,
            'status'             => $this->status,
            'is_active'          => $this->is_active,
            'deadline'           => $this->deadline,
            'creator'            => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'modules'   => CourseModuleResource::collection($this->whenLoaded('modules')),
            'analytics' => $this->whenLoaded('analytics', fn () => [
                'total_enrollments' => $this->analytics?->total_enrollments,
                'total_completions' => $this->analytics?->total_completions,
                'total_modules'     => $this->analytics?->total_modules,
                'total_contents'    => $this->analytics?->total_contents,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
