<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Video;
use App\Models\VideoQuality;

class ImportVideoQualities extends LegacyImportCommand
{
    protected $signature = 'legacy:import-video-qualities';

    protected $description = 'Import video_qualities - near 1:1, remaps video_id.';

    protected array $videoMap = [];

    protected function legacyTable(): string
    {
        return 'video_qualities';
    }

    protected function newModel(): string
    {
        return VideoQuality::class;
    }

    protected function beforeImport(): void
    {
        $this->videoMap = Video::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newVideoId = $this->videoMap[$old['video_id']] ?? null;

        if ($newVideoId === null) {
            $this->error("No imported Video for legacy video_id={$old['video_id']} (video_quality legacy_id={$old['id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'video_id' => $newVideoId,
            'quality' => $old['quality'],
            'file_path' => $old['file_path'],
            'file_size' => $old['file_size'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
