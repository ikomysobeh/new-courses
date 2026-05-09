<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnlineCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'description'        => $this->description,
            'image_path'         => $this->image_path,
            'level'              => $this->level,
            'estimated_duration' => $this->estimated_duration,
            'status'             => $this->status,
            'is_active'          => $this->is_active,
            'deadline'           => $this->deadline,
            'modules_count'      => $this->modules_count ?? null,
            'assignments_count'  => $this->assignments_count ?? null,
            'creator'            => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
