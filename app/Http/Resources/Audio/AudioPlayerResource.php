<?php

namespace App\Http\Resources\Audio;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudioPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $progress = $this->whenLoaded('progress', fn () => $this->progress->first());

        return [
            'audio' => new AudioResource($this->resource),
            'progress' => $progress ? new AudioProgressResource($progress) : null,
        ];
    }
}
