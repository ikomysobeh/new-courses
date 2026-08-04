<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoCategory;

class ImportVideos extends LegacyImportCommand
{
    protected $signature = 'legacy:import-videos';

    protected $description = 'Import videos. video_category_id remapped via video_categories.legacy_id (4 old rows have no category - assigned to a new "Uncategorized" category per client decision, since the new column is NOT NULL). created_by remapped via users.legacy_id. Drops google_drive_url/storage_type/mime_type/duration/is_active/subtitle_status/order_number/is_required.';

    protected array $categoryMap = [];

    protected array $userMap = [];

    protected int $uncategorizedId;

    protected function legacyTable(): string
    {
        return 'videos';
    }

    protected function newModel(): string
    {
        return Video::class;
    }

    protected function beforeImport(): void
    {
        $this->categoryMap = VideoCategory::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();

        $this->uncategorizedId = VideoCategory::firstOrCreate(
            ['name' => 'Uncategorized'],
            ['slug' => 'uncategorized', 'sort_order' => 999]
        )->id;
    }

    protected function mapRow(array $old): ?array
    {
        $newCreatedBy = $this->userMap[$old['created_by']] ?? null;

        if ($newCreatedBy === null) {
            $this->error("No imported User for legacy created_by={$old['created_by']} (video legacy_id={$old['id']})");

            return null;
        }

        $newCategoryId = $old['content_category_id'] !== null
            ? ($this->categoryMap[$old['content_category_id']] ?? null)
            : $this->uncategorizedId;

        if ($newCategoryId === null) {
            $this->error("No imported VideoCategory for legacy content_category_id={$old['content_category_id']} (video legacy_id={$old['id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'name' => $old['name'],
            'description' => $old['description'],
            'file_path' => $old['file_path'],
            'file_size' => $old['file_size'],
            'duration_seconds' => $old['duration_seconds'],
            'thumbnail_path' => $old['thumbnail_path'],
            'subtitle_vtt_path' => $old['subtitle_vtt_path'],
            'video_category_id' => $newCategoryId,
            'transcode_status' => $old['transcode_status'],
            'created_by' => $newCreatedBy,
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
