<?php

namespace App\Http\Resources\Blog;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;
use Illuminate\Support\Facades\Storage;

class BlogMediaResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $mediaType = class_basename($this->mediable_type ?? '');

        if ($mediaType === 'Video') {
            $media = $this->mediable;
            return [
                'type'             => 'video',
                'id'               => $media?->id,
                'name'             => $media?->name,
                'duration_seconds' => $media?->duration_seconds,
                'thumbnail_url'    => $media?->thumbnail_path
                    ? Storage::disk('public')->url($media->thumbnail_path)
                    : null,
                'stream_url'       => $this->stream_url ?? null,
            ];
        }

        if ($mediaType === 'Audio') {
            $media = $this->mediable;
            return [
                'type'       => 'audio',
                'id'         => $media?->id,
                'name'       => $media?->name,
                'duration'   => $media?->duration,
                'thumbnail_url' => $media?->thumbnail_path
                    ? Storage::disk('public')->url($media->thumbnail_path)
                    : null,
                'stream_url' => $this->stream_url ?? null,
            ];
        }

        return [];
    }
}
