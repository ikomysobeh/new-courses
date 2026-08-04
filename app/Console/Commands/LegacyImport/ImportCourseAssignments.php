<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseAvailability;
use App\Models\User;

class ImportCourseAssignments extends LegacyImportCommand
{
    protected $signature = 'legacy:import-course-assignments';

    protected $description = "Import course_assignments. Drops status/responded_at/completed_at - that data isn't lost, it's tracked separately by course_registrations/course_completions (imported next), so the assignment row itself is just \"who was assigned when\".";

    protected array $courseMap = [];

    protected array $userMap = [];

    protected array $availabilityMap = [];

    protected function legacyTable(): string
    {
        return 'course_assignments';
    }

    protected function newModel(): string
    {
        return CourseAssignment::class;
    }

    protected function beforeImport(): void
    {
        $this->courseMap = Course::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->availabilityMap = CourseAvailability::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newCourseId = $this->courseMap[$old['course_id']] ?? null;
        $newUserId = $this->userMap[$old['user_id']] ?? null;
        $newAssignedBy = $this->userMap[$old['assigned_by']] ?? null;

        if ($newCourseId === null || $newUserId === null || $newAssignedBy === null) {
            $this->error("Unresolved mapping for course_assignment legacy_id={$old['id']} (course_id={$old['course_id']}, user_id={$old['user_id']}, assigned_by={$old['assigned_by']})");

            return null;
        }

        $newAvailabilityId = null;

        if ($old['course_availability_id'] !== null) {
            $newAvailabilityId = $this->availabilityMap[$old['course_availability_id']] ?? null;

            if ($newAvailabilityId === null) {
                $this->error("No imported CourseAvailability for legacy course_availability_id={$old['course_availability_id']} (course_assignment legacy_id={$old['id']})");

                return null;
            }
        }

        return [
            'legacy_id' => $old['id'],
            'course_id' => $newCourseId,
            'user_id' => $newUserId,
            'assigned_by' => $newAssignedBy,
            'course_availability_id' => $newAvailabilityId,
            'assigned_at' => $old['assigned_at'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
