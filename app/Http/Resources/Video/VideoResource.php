<?php

namespace App\Http\Resources\Video;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'description'      => $this->description,
            'file_path'        => $this->file_path,
            'file_size'        => $this->file_size,
            'duration_seconds' => $this->duration_seconds,
            'thumbnail_path'   => $this->thumbnail_path,
            'transcode_status' => $this->transcode_status,
            'video_category'   => $this->whenLoaded('videoCategory', fn () => [
                'id'   => $this->videoCategory->id,
                'name' => $this->videoCategory->name,
            ]),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
