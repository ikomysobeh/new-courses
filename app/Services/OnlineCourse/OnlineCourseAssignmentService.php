<?php

namespace App\Services\OnlineCourse;

use App\Events\OnlineCourseAssigned;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\User;
use App\Models\UserCourseProgress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OnlineCourseAssignmentService
{
    public function getAllAssignments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CourseOnlineAssignment::query()
            ->with(['course:id,name,deadline', 'user', 'assignedBy'])
            // Per-row completion flag (1 = this user completed this course) so the
            // resource can derive `is_overdue` without an N+1 query.
            ->addSelect(['course_completed' => UserCourseProgress::query()
                ->selectRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
                ->whereColumn('user_course_progress.user_id', 'course_online_assignments.user_id')
                ->whereColumn('user_course_progress.course_online_id', 'course_online_assignments.course_online_id')
                ->limit(1)])
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

        // Overdue = the course deadline has passed AND the user has not completed it.
        if (isset($filters['is_overdue']) && $filters['is_overdue'] !== '') {
            $wantOverdue = filter_var($filters['is_overdue'], FILTER_VALIDATE_BOOLEAN);

            $completedExists = function ($q) {
                $q->from('user_course_progress')
                    ->whereColumn('user_course_progress.user_id', 'course_online_assignments.user_id')
                    ->whereColumn('user_course_progress.course_online_id', 'course_online_assignments.course_online_id')
                    ->where('status', 'completed');
            };

            if ($wantOverdue) {
                $query->whereHas('course', fn ($c) => $c
                    ->whereNotNull('deadline')->where('deadline', '<', now()))
                    ->whereNotExists($completedExists);
            } else {
                $query->where(function ($outer) use ($completedExists) {
                    $outer->whereDoesntHave('course', fn ($c) => $c
                        ->whereNotNull('deadline')->where('deadline', '<', now()))
                        ->orWhereExists($completedExists);
                });
            }
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