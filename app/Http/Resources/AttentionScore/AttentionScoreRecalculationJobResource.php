<?php

namespace App\Http\Resources\AttentionScore;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class AttentionScoreRecalculationJobResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'attention_score_config_id' => $this->attention_score_config_id,
            'status'             => $this->status,
            'total_sessions'     => $this->total_sessions,
            'processed_sessions' => $this->processed_sessions,
            'started_at'         => $this->started_at?->toIso8601String(),
            'finished_at'        => $this->finished_at?->toIso8601String(),
            'error_message'      => $this->error_message,
        ];
    }
}
