<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\CourseModule;
use App\Models\CourseOnline;

class ImportCourseModules extends LegacyImportCommand
{
    protected $signature = 'legacy:import-course-modules';

    protected $description = 'Import course_modules. course_online_id remapped via course_onlines.legacy_id. is_required/is_active dropped (not in new schema).';

    protected array $courseOnlineMap = [];

    protected function legacyTable(): string
    {
        return 'course_modules';
    }

    protected function newModel(): string
    {
        return CourseModule::class;
    }

    protected function beforeImport(): void
    {
        $this->courseOnlineMap = CourseOnline::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newCourseOnlineId = $this->courseOnlineMap[$old['course_online_id']] ?? null;

        if ($newCourseOnlineId === null) {
            $this->error("No imported CourseOnline for legacy course_online_id={$old['course_online_id']} (module legacy_id={$old['id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'course_online_id' => $newCourseOnlineId,
            'name' => $old['name'],
            'description' => $old['description'],
            'order_number' => $old['order_number'],
            'estimated_duration' => $old['estimated_duration'],
            'has_quiz' => $old['has_quiz'],
            'quiz_required' => $old['quiz_required'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
