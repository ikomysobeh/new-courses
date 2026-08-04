<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\VideoCategory;

class ImportVideoCategories extends LegacyImportCommand
{
    protected $signature = 'legacy:import-video-categories';

    protected $description = 'Import content_categories -> video_categories (renamed). Drops is_active/description (not in new schema).';

    protected function legacyTable(): string
    {
        return 'content_categories';
    }

    protected function newModel(): string
    {
        return VideoCategory::class;
    }

    protected function mapRow(array $old): ?array
    {
        return [
            'legacy_id' => $old['id'],
            'name' => $old['name'],
            'slug' => $old['slug'],
            'sort_order' => $old['sort_order'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
