<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Course;
use App\Models\CourseCompletion;
use App\Models\User;

class ImportCourseCompletions extends LegacyImportCommand
{
    protected $signature = 'legacy:import-course-completions';

    protected $description = 'Import course_completions - near 1:1.';

    protected array $userMap = [];

    protected array $courseMap = [];

    protected function legacyTable(): string
    {
        return 'course_completions';
    }

    protected function newModel(): string
    {
        return CourseCompletion::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->courseMap = Course::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newUserId = $this->userMap[$old['user_id']] ?? null;
        $newCourseId = $this->courseMap[$old['course_id']] ?? null;

        if ($newUserId === null || $newCourseId === null) {
            $this->error("Unresolved mapping for course_completion legacy_id={$old['id']} (user_id={$old['user_id']}, course_id={$old['course_id']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'user_id' => $newUserId,
            'course_id' => $newCourseId,
            'completed_at' => $old['completed_at'],
            'rating' => $old['rating'],
            'feedback' => $old['feedback'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
