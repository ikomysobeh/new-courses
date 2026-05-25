<?php

namespace App\Services\Blog\Media;

use App\Models\Audio;
use App\Models\Video;
use Illuminate\Support\Facades\URL;

class BlogMediaService
{
    public function getAvailableVideos(): \Illuminate\Database\Eloquent\Collection
    {
        return Video::query()
            ->where('transcode_status', 'completed')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'thumbnail_path', 'duration_seconds']);
    }

    public function getAvailableAudios(): \Illuminate\Database\Eloquent\Collection
    {
        return Audio::query()
            ->whereNotNull('local_path')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'thumbnail_path', 'duration']);
    }

    public function validateMediableSelection(?string $type, ?int $id): void
    {
        if ($type === null && $id === null) {
            return;
        }

        if ($type === 'App\\Models\\Video') {
            abort_unless(
                Video::where('id', $id)->where('transcode_status', 'completed')->whereNull('deleted_at')->exists(),
                422,
                'Selected video is not available.'
            );
            return;
        }

        if ($type === 'App\\Models\\Audio') {
            abort_unless(
                Audio::where('id', $id)->whereNotNull('local_path')->whereNull('deleted_at')->exists(),
                422,
                'Selected audio is not available.'
            );
            return;
        }

        abort(422, 'Invalid mediable type.');
    }

    public function resolveStreamUrl(?string $type, ?int $id): ?string
    {
        if (!$type || !$id) {
            return null;
        }

        if ($type === 'App\\Models\\Video') {
            return URL::temporarySignedRoute(
                'media.blog-video',
                now()->addHours(4),
                ['video_id' => $id]
            );
        }

        if ($type === 'App\\Models\\Audio') {
            return URL::temporarySignedRoute(
                'media.blog-audio',
                now()->addHours(4),
                ['audio_id' => $id]
            );
        }

        return null;
    }
}
