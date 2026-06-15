<?php

namespace App\Http\Resources\Video;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class VideoResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $thumbnailPath = $this->normalizeThumbnailPath($this->thumbnail_path);

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'description'      => $this->description,
            'file_path'        => $this->file_path,
            'file_size'        => $this->file_size,
            'duration_seconds' => $this->duration_seconds,
            'thumbnail_path'   => $thumbnailPath
                ? $this->buildThumbnailUrl($thumbnailPath)
                : null,
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

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    }

    private function normalizeThumbnailPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return ltrim(str_replace('\\', '/', $path), '/');
    }

    private function buildThumbnailUrl(string $path): string
    {
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));

        return $this->normalizeUrl(asset('storage/' . $encodedPath));
    }

    private function normalizeUrl(string $url): string
    {
        return preg_replace('#(?<!:)/{2,}#', '/', $url) ?? $url;
    }
}
