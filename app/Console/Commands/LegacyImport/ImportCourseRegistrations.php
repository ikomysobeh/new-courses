<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Course;
use App\Models\CourseAvailability;
use App\Models\CourseRegistration;
use App\Models\User;

class ImportCourseRegistrations extends LegacyImportCommand
{
    protected $signature = 'legacy:import-course-registrations';

    protected $description = 'Import course_registrations - near 1:1.';

    protected array $userMap = [];

    protected array $courseMap = [];

    protected array $availabilityMap = [];

    protected function legacyTable(): string
    {
        return 'course_registrations';
    }

    protected function newModel(): string
    {
        return CourseRegistration::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->courseMap = Course::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->availabilityMap = CourseAvailability::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newUserId = $this->userMap[$old['user_id']] ?? null;
        $newCourseId = $this->courseMap[$old['course_id']] ?? null;

        if ($newUserId === null || $newCourseId === null) {
            $this->error("Unresolved mapping for course_registration legacy_id={$old['id']} (user_id={$old['user_id']}, course_id={$old['course_id']})");

            return null;
        }

        $newAvailabilityId = null;

        if ($old['course_availability_id'] !== null) {
            $newAvailabilityId = $this->availabilityMap[$old['course_availability_id']] ?? null;

            if ($newAvailabilityId === null) {
                $this->error("No imported CourseAvailability for legacy course_availability_id={$old['course_availability_id']} (course_registration legacy_id={$old['id']})");

                return null;
            }
        }

        return [
            'legacy_id' => $old['id'],
            'user_id' => $newUserId,
            'course_id' => $newCourseId,
            'course_availability_id' => $newAvailabilityId,
            'status' => $old['status'],
            'registered_at' => $old['registered_at'],
            'completed_at' => $old['completed_at'],
            'rating' => $old['rating'],
            'feedback' => $old['feedback'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
