<?php

namespace App\Http\Resources\Audio;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudioProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'audio_id' => $this->audio_id,
            'current_time' => (float) $this->current_time,
            'total_listened_time' => (int) $this->total_listened_time,
            'completion_percentage' => (float) $this->completion_percentage,
            'is_completed' => (bool) $this->is_completed,
            'last_accessed_at' => $this->last_accessed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
