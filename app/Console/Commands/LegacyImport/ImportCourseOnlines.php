<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\CourseOnline;
use App\Models\User;

class ImportCourseOnlines extends LegacyImportCommand
{
    protected $signature = 'legacy:import-course-onlines';

    protected $description = 'Import course_online -> course_onlines. created_by remapped via users.legacy_id. status=published for all (old schema had no publish-state concept, only is_active), matching the courses decision.';

    protected array $userMap = [];

    protected function legacyTable(): string
    {
        return 'course_online';
    }

    protected function newModel(): string
    {
        return CourseOnline::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newCreatedBy = $this->userMap[$old['created_by']] ?? null;

        if ($newCreatedBy === null) {
            $this->error("No imported User for legacy created_by={$old['created_by']} (course_online legacy_id={$old['id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'name' => $old['name'],
            'description' => $old['description'],
            'image_path' => $old['image_path'],
            'level' => $old['difficulty_level'],
            'estimated_duration' => $old['estimated_duration'],
            'status' => 'published',
            'is_active' => $old['is_active'],
            'deadline' => $old['deadline'],
            'created_by' => $newCreatedBy,
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
