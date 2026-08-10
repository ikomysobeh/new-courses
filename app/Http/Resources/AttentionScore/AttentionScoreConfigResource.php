<?php

namespace App\Http\Resources\AttentionScore;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class AttentionScoreConfigResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'is_active'  => $this->is_active,
            'config'     => $this->config,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
