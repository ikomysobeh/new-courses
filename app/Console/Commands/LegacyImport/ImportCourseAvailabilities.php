<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Course;
use App\Models\CourseAvailability;

class ImportCourseAvailabilities extends LegacyImportCommand
{
    protected $signature = 'legacy:import-course-availabilities';

    protected $description = 'Import course_availabilities. Negative capacity values (a pre-existing data-quality issue in production) are clamped to 0 per client decision.';

    protected array $courseMap = [];

    protected function legacyTable(): string
    {
        return 'course_availabilities';
    }

    protected function newModel(): string
    {
        return CourseAvailability::class;
    }

    protected function beforeImport(): void
    {
        $this->courseMap = Course::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newCourseId = $this->courseMap[$old['course_id']] ?? null;

        if ($newCourseId === null) {
            $this->error("No imported Course for legacy course_id={$old['course_id']} (availability legacy_id={$old['id']})");

            return null;
        }

        $capacity = (int) $old['capacity'];

        if ($capacity < 0) {
            $this->warn("Clamping negative capacity ({$capacity}) to 0 for availability legacy_id={$old['id']}");
            $capacity = 0;
        }

        return [
            'legacy_id' => $old['id'],
            'course_id' => $newCourseId,
            'start_date' => $old['start_date'],
            'end_date' => $old['end_date'],
            'capacity' => $capacity,
            'sessions' => $old['sessions'],
            'duration_weeks' => $old['duration_weeks'],
            'status' => $old['status'],
            'notes' => $old['notes'],
            'days_of_week' => $old['days_of_week'],
            'session_time_shift_1' => $old['session_time'],
            'session_time_shift_2' => $old['session_time_shift_2'],
            'session_time_shift_3' => $old['session_time_shift_3'],
            'session_duration_minutes' => $old['session_duration_minutes'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
