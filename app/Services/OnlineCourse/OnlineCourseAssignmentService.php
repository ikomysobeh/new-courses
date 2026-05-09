<?php

namespace App\Services\OnlineCourse;

use App\Models\CourseOnlineAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OnlineCourseAssignmentService
{
    public function getAllAssignments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CourseOnlineAssignment::query()
            ->with(['course', 'user'])
            ->latest();

        if (!empty($filters['course_online_id'])) {
            $query->where('course_online_id', $filters['course_online_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->paginate($perPage);
    }

    public function createAssignment(array $data): CourseOnlineAssignment
    {
        // Check existence including soft-deleted rows (uniqueness must hold over all rows)
        $exists = CourseOnlineAssignment::withTrashed()
            ->where('course_online_id', $data['course_online_id'])
            ->where('user_id', $data['user_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'user_id' => ['This user is already assigned to this course.'],
            ]);
        }

        return CourseOnlineAssignment::query()->create($data);
    }

    public function deleteAssignment(int $id): void
    {
        $assignment = CourseOnlineAssignment::query()->findOrFail($id);
        $assignment->delete();
    }

    public function getAssignmentCards(): array
    {
        $total   = CourseOnlineAssignment::query()->count();
        $courses = CourseOnlineAssignment::query()->distinct('course_online_id')->count('course_online_id');
        $users   = CourseOnlineAssignment::query()->distinct('user_id')->count('user_id');

        return [
            ['key' => 'total_assignments', 'title' => 'Total Assignments',   'value' => $total],
            ['key' => 'courses_assigned',  'title' => 'Courses With Users',  'value' => $courses],
            ['key' => 'users_assigned',    'title' => 'Users Assigned',      'value' => $users],
        ];
    }
}
