<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\User;

class ImportCourseOnlineAssignments extends LegacyImportCommand
{
    protected $signature = 'legacy:import-course-online-assignments';

    protected $description = "Import course_online_assignments. Drops status/progress_percentage/current_module_id/deadline/is_overdue/started_at/completed_at - that data moves to user_course_progress (imported later in this phase), so the assignment row itself is just \"who was assigned when\".";

    protected array $courseOnlineMap = [];

    protected array $userMap = [];

    protected function legacyTable(): string
    {
        return 'course_online_assignments';
    }

    protected function newModel(): string
    {
        return CourseOnlineAssignment::class;
    }

    protected function beforeImport(): void
    {
        $this->courseOnlineMap = CourseOnline::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newCourseOnlineId = $this->courseOnlineMap[$old['course_online_id']] ?? null;
        $newUserId = $this->userMap[$old['user_id']] ?? null;
        $newAssignedBy = $this->userMap[$old['assigned_by']] ?? null;

        if ($newCourseOnlineId === null || $newUserId === null || $newAssignedBy === null) {
            $this->error("Unresolved mapping for course_online_assignment legacy_id={$old['id']} (course_online_id={$old['course_online_id']}, user_id={$old['user_id']}, assigned_by={$old['assigned_by']})");

            return null;
        }

        return [
            'legacy_id' => $old['id'],
            'course_online_id' => $newCourseOnlineId,
            'user_id' => $newUserId,
            'assigned_by' => $newAssignedBy,
            'assigned_at' => $old['assigned_at'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
