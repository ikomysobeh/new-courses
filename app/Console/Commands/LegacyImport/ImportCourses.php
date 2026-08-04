<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Course;
use App\Models\User;

class ImportCourses extends LegacyImportCommand
{
    protected $signature = 'legacy:import-courses';

    protected $description = 'Import traditional courses. created_by is not tracked in the old schema, so per client decision every imported course is attributed to Harry@pneunited.com and given status=published (old status tracked schedule progress, not publish state).';

    /**
     * Old id of Harry@pneunited.com, the agreed default creator for legacy courses.
     */
    protected const DEFAULT_CREATOR_LEGACY_ID = 2;

    protected int $defaultCreatorId;

    protected function legacyTable(): string
    {
        return 'courses';
    }

    protected function newModel(): string
    {
        return Course::class;
    }

    protected function beforeImport(): void
    {
        $creator = User::where('legacy_id', self::DEFAULT_CREATOR_LEGACY_ID)->first();

        if (! $creator) {
            throw new \RuntimeException('Default course creator (legacy user id '.self::DEFAULT_CREATOR_LEGACY_ID.') not found - run legacy:import-users first.');
        }

        $this->defaultCreatorId = $creator->id;
    }

    protected function mapRow(array $old): ?array
    {
        return [
            'legacy_id' => $old['id'],
            'name' => $old['name'],
            'description' => $old['description'],
            'image_path' => $old['image_path'],
            'level' => $old['level'],
            'duration' => $old['duration'],
            'status' => 'published',
            'privacy' => $old['privacy'],
            'created_by' => $this->defaultCreatorId,
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
