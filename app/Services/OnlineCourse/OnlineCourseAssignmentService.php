<?php

namespace App\Services\OnlineCourse;

use App\Events\OnlineCourseAssigned;
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

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', function ($uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                       ->orWhere('email', 'like', "%{$term}%");
                })->orWhereHas('course', function ($cq) use ($term) {
                    $cq->where('name', 'like', "%{$term}%");
                });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Bulk-assign users to a course. Duplicate assignments are silently skipped.
     *
     * @return array{assignments: CourseOnlineAssignment[], meta: array{created: int, skipped: int}}
     */
    public function createAssignment(array $data, User $admin): array
    {
        $courseId = $data['course_online_id'];
        $userIds  = $data['user_ids'];

        $created = [];
        $skipped = 0;

        foreach ($userIds as $userId) {
            // Skip if assignment already exists
            $exists = CourseOnlineAssignment::query()
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
            ]);

            $assignment->load(['course', 'user', 'assignedBy']);

            if ($data['send_notification'] ?? false) {
                OnlineCourseAssigned::dispatch(
                    $assignment->course,
                    $assignment->user,
                    $admin,
                );
            }

            $created[] = $assignment;
        }

        return [
            'assignments' => $created,
            'meta'        => [
                'created' => count($created),
                'skipped' => $skipped,
            ],
        ];
    }

    public function deleteAssignment(int $id): void
    {
        CourseOnlineAssignment::query()->findOrFail($id)->delete();
    }

    public function getAssignmentCards(): array
    {
        $total         = CourseOnlineAssignment::query()->count();
        $assignedUsers = CourseOnlineAssignment::query()
            ->distinct('user_id')
            ->count('user_id');
        $activeCourses = CourseOnlineAssignment::query()
            ->distinct('course_online_id')
            ->count('course_online_id');

        return [
            ['key' => 'total_assignments', 'title' => 'Total Assignments', 'value' => $total],
            ['key' => 'assigned_users',    'title' => 'Assigned Users',    'value' => $assignedUsers],
            ['key' => 'active_courses',    'title' => 'Active Courses',    'value' => $activeCourses],
        ];
    }
}