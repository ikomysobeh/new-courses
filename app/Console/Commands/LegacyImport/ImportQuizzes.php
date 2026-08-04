<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\Quiz;

class ImportQuizzes extends LegacyImportCommand
{
    protected $signature = 'legacy:import-quizzes';

    protected $description = 'Import quizzes. course_id/course_online_id/module_id remapped independently (nullable, a quiz belongs to exactly one of the three). Drops is_module_quiz/has_deadline/enforce_deadline/allows_extensions (not in new schema).';

    protected array $courseMap = [];

    protected array $courseOnlineMap = [];

    protected array $moduleMap = [];

    protected function legacyTable(): string
    {
        return 'quizzes';
    }

    protected function newModel(): string
    {
        return Quiz::class;
    }

    protected function beforeImport(): void
    {
        $this->courseMap = Course::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->courseOnlineMap = CourseOnline::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->moduleMap = CourseModule::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newCourseId = null;
        $newCourseOnlineId = null;
        $newModuleId = null;

        if ($old['course_id'] !== null) {
            $newCourseId = $this->courseMap[$old['course_id']] ?? null;

            if ($newCourseId === null) {
                $this->error("No imported Course for legacy course_id={$old['course_id']} (quiz legacy_id={$old['id']})");

                return null;
            }
        }

        if ($old['course_online_id'] !== null) {
            $newCourseOnlineId = $this->courseOnlineMap[$old['course_online_id']] ?? null;

            if ($newCourseOnlineId === null) {
                $this->error("No imported CourseOnline for legacy course_online_id={$old['course_online_id']} (quiz legacy_id={$old['id']})");

                return null;
            }
        }

        if ($old['module_id'] !== null) {
            $newModuleId = $this->moduleMap[$old['module_id']] ?? null;

            if ($newModuleId === null) {
                $this->error("No imported CourseModule for legacy module_id={$old['module_id']} (quiz legacy_id={$old['id']})");

                return null;
            }
        }

        return [
            'legacy_id' => $old['id'],
            'course_id' => $newCourseId,
            'course_online_id' => $newCourseOnlineId,
            'module_id' => $newModuleId,
            'title' => $old['title'],
            'description' => $old['description'],
            'required_to_proceed' => $old['required_to_proceed'],
            'max_attempts' => $old['max_attempts'],
            'retry_delay_hours' => $old['retry_delay_hours'],
            'show_correct_answers' => $old['show_correct_answers'],
            'deadline' => $old['deadline'],
            'time_limit_minutes' => $old['time_limit_minutes'],
            'status' => $old['status'],
            'total_points' => $old['total_points'],
            'pass_threshold' => $old['pass_threshold'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
