<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Audio;
use App\Models\AudioCategory;

class ImportAudios extends LegacyImportCommand
{
    protected $signature = 'legacy:import-audios';

    protected $description = 'Import audios. audio_category_id is NOT NULL in the new schema, but all 4 legacy audios have no category (old audio_categories was empty) - assigned to a new "Uncategorized" category, same pattern as videos. Drops google_cloud_url/storage_type/thumbnail_url/created_by (not in new schema).';

    protected int $uncategorizedId;

    protected function legacyTable(): string
    {
        return 'audios';
    }

    protected function newModel(): string
    {
        return Audio::class;
    }

    protected function beforeImport(): void
    {
        $this->uncategorizedId = AudioCategory::firstOrCreate(
            ['name' => 'Uncategorized'],
            ['slug' => 'uncategorized', 'sort_order' => 999]
        )->id;
    }

    protected function mapRow(array $old): ?array
    {
        if ($old['audio_category_id'] !== null) {
            $this->warn("audios legacy_id={$old['id']} has a non-null audio_category_id ({$old['audio_category_id']}) but no ImportAudioCategories command exists yet (old audio_categories was empty in the dataset this was written against) - falling back to Uncategorized.");
        }

        return [
            'legacy_id' => $old['id'],
            'name' => $old['name'],
            'description' => $old['description'],
            'local_path' => $old['local_path'],
            'duration' => $old['duration'],
            'thumbnail_path' => $old['thumbnail_path'],
            'audio_category_id' => $this->uncategorizedId,
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
