<?php

namespace App\Http\Resources\AttentionScore;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class AttentionScoreConfigHistoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'is_active'    => $this->is_active,
            'created_by'   => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
