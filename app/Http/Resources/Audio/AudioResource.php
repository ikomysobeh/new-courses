<?php

namespace App\Http\Resources\Audio;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AudioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $progress = null;
        if ($this->relationLoaded('progress')) {
            $progress = $this->progress->first();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'duration' => $this->duration,
            'audio_category_id' => $this->audio_category_id,
            'audio_category' => $this->whenLoaded('audioCategory', fn () => [
                'id' => $this->audioCategory->id,
                'name' => $this->audioCategory->name,
            ]),
            'thumbnail_path' => $this->thumbnail_path
                ? Storage::disk('public')->url($this->thumbnail_path)
                : null,
            'has_audio_file' => ! empty($this->local_path),
            'progress' => $this->when(
                $this->relationLoaded('progress') && $progress !== null,
                fn () => [
                    'current_time'         => (float) $progress->current_time,
                    'total_listened_time'  => (int) $progress->total_listened_time,
                    'completion_percentage'=> (float) $progress->completion_percentage,
                    'is_completed'         => (bool) $progress->is_completed,
                    'last_accessed_at'     => $progress->last_accessed_at,
                ]
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
