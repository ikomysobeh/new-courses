<?php

namespace App\Http\Resources\User\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCourseListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $progressCollection    = $this->resource->userProgress ?? collect();
        $assignmentCollection  = $this->resource->assignmentForUser ?? collect();

        $progress   = $progressCollection instanceof \Illuminate\Support\Collection
            ? $progressCollection->first()
            : null;

        $assignment = $assignmentCollection instanceof \Illuminate\Support\Collection
            ? $assignmentCollection->first()
            : null;

        return [
            'id'                      => $this->id,
            'title'                   => $this->name,
            'description'             => $this->description,
            'thumbnail_url'           => $this->image_path,
            'total_modules'           => $this->modules_count ?? $this->modules->count(),
            'total_content_items'     => $this->total_content_items ?? 0,
            'progress_percentage'     => $progress?->progress_percentage ?? 0,
            'status'                  => $progress?->status ?? 'not_started',
            'completed_content_items' => $progress?->completed_content_items ?? 0,
            'started_at'              => $progress?->started_at,
            'completed_at'            => $progress?->completed_at,
            'last_accessed_at'        => $progress?->last_accessed_at,
            'assigned_at'             => $assignment?->assigned_at,
        ];
    }
}
