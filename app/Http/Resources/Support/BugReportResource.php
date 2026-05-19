<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BugReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'priority'            => $this->priority,
            'status'              => $this->status,
            'title'               => $this->title,
            'description'         => $this->description,
            'steps_to_reproduce'  => $this->steps_to_reproduce,
            'page_url'            => $this->page_url,
            'resolved_at'         => $this->resolved_at?->toISOString(),
            'reported_by'         => $this->when($this->relationLoaded('reporter'), fn () => [
                'id'   => $this->reporter->id,
                'name' => $this->reporter->name,
            ]),
            'assigned_to'         => $this->when($this->relationLoaded('assignee'), fn () => $this->assignee ? [
                'id'   => $this->assignee->id,
                'name' => $this->assignee->name,
            ] : null),
            'created_at'          => $this->created_at?->toISOString(),
        ];
    }
}
