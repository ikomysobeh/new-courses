<?php

namespace App\Services\OnlineCourse;

use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OnlineCourseAssignmentService
{
    public function getAllAssignments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CourseOnlineAssignment::query()
            ->with(['course', 'user', 'assignedBy'])
            ->latest();

        if (!empty($filters['course_online_id'])) {
            $query->where('course_online_id', $filters['course_online_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['is_overdue']) && $filters['is_overdue'] !== '') {
            $query->where('is_overdue', (bool) $filters['is_overdue']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Bulk-assign users to a course. Duplicate assignments (including soft-deleted) are silently skipped.
     *
     * @return array{assignments: CourseOnlineAssignment[], meta: array{created: int, skipped: int}}
     */
    public function createAssignment(array $data, User $admin): array
    {
        $courseId = $data['course_online_id'];
        $userIds  = $data['user_ids'];
        $deadline = $data['deadline'] ?? null;

        $created = [];
        $skipped = 0;

        foreach ($userIds as $userId) {
            // Skip if assignment already exists (active or soft-deleted)
            $exists = CourseOnlineAssignment::withTrashed()
                ->where('course_online_id', $courseId)
                ->where('user_id', $userId)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $assignment = CourseOnlineAssignment::query()->create([
                'course_online_id' => $courseId,
                'user_id'          => $userId,
                'assigned_by'      => $admin->id,
                'assigned_at'      => now(),
                'deadline'         => $deadline,
            ]);

            $created[] = $assignment->load(['course', 'user', 'assignedBy']);
        }

        return [
            'assignments' => $created,
            'meta'        => [
                'created' => count($created),
                'skipped' => $skipped,
            ],
        ];
    }

    public function deleteAssignment(int $id, User $admin): void
    {
        $assignment = CourseOnlineAssignment::query()->findOrFail($id);

        $assignment->update([
            'unassigned_at' => now(),
            'unassigned_by' => $admin->id,
        ]);

        $assignment->delete();
    }

    public function getAssignmentCards(): array
    {
        $total        = CourseOnlineAssignment::query()->whereNull('deleted_at')->count();
        $activeCourses = CourseOnlineAssignment::query()
            ->whereNull('deleted_at')
            ->distinct('course_online_id')
            ->count('course_online_id');

        return [
            ['key' => 'total_assignments', 'title' => 'Total Assignments', 'value' => $total],
            ['key' => 'active_courses',    'title' => 'Active Courses',    'value' => $activeCourses],
        ];
    }
}


