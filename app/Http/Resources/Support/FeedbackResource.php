<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'title'          => $this->title,
            'description'    => $this->description,
            'status'         => $this->status,
            'admin_response' => $this->admin_response,
            'user'           => $this->when($this->relationLoaded('user'), fn () => [
                'id'         => $this->user->id,
                'name'       => $this->user->name,
                'department' => $this->when(
                    $this->user->relationLoaded('department'),
                    fn () => [
                        'id'   => optional($this->user->department)->id,
                        'name' => optional($this->user->department)->name,
                    ]
                ),
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
