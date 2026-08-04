<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Clocking;
use App\Models\Course;
use App\Models\User;

class ImportClockings extends LegacyImportCommand
{
    protected $signature = 'legacy:import-clockings';

    protected $description = 'Import clockings - near 1:1. duration_in_minutes is signed in the old schema and has real negative values in production (a pre-existing data-quality issue); clamped to 0 for the new unsigned column, same policy already agreed for course_availabilities.capacity.';

    protected array $userMap = [];

    protected array $courseMap = [];

    protected function legacyTable(): string
    {
        return 'clockings';
    }

    protected function newModel(): string
    {
        return Clocking::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->courseMap = Course::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newUserId = $this->userMap[$old['user_id']] ?? null;

        if ($newUserId === null) {
            $this->error("No imported User for legacy user_id={$old['user_id']} (clocking legacy_id={$old['id']})");

            return null;
        }

        $newCourseId = null;

        if ($old['course_id'] !== null) {
            $newCourseId = $this->courseMap[$old['course_id']] ?? null;

            if ($newCourseId === null) {
                $this->error("No imported Course for legacy course_id={$old['course_id']} (clocking legacy_id={$old['id']})");

                return null;
            }
        }

        $duration = $old['duration_in_minutes'];

        if ($duration !== null && $duration < 0) {
            $this->warn("Clamping negative duration_in_minutes ({$duration}) to 0 for clocking legacy_id={$old['id']}");
            $duration = 0;
        }

        return [
            'legacy_id' => $old['id'],
            'user_id' => $newUserId,
            'course_id' => $newCourseId,
            'clock_in' => $old['clock_in'],
            'clock_out' => $old['clock_out'],
            'duration_in_minutes' => $duration,
            'rating' => $old['rating'],
            'comment' => $old['comment'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
