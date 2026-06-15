<?php

namespace App\Services\Reporting\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Attendance (clocking) report for live/traditional courses.
 * Source: clockings (joined to users, optional course).
 * A null course_id represents general (non-course) attendance.
 */
class AttendanceQueryService
{
    public function baseQuery(array $filters = []): Builder
    {
        $q = DB::table('clockings as k')
            ->join('users as u', 'u.id', '=', 'k.user_id')
            ->leftJoin('courses as c', 'c.id', '=', 'k.course_id')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->select(
                'k.id',
                'k.user_id',
                'u.name as user_name',
                'u.email as user_email',
                'd.id as department_id',
                'd.name as department_name',
                'k.course_id',
                'c.name as course_name',
                'k.clock_in',
                'k.clock_out',
                'k.duration_in_minutes',
                'k.rating',
                'k.comment'
            );

        if (! empty($filters['user_id'])) {
            $q->where('k.user_id', $filters['user_id']);
        }
        if (! empty($filters['department_id'])) {
            $q->where('u.department_id', $filters['department_id']);
        }
        // course_id: numeric id, or the literal "general" for non-course attendance
        if (isset($filters['course_id']) && $filters['course_id'] !== '' && $filters['course_id'] !== null) {
            if ($filters['course_id'] === 'general') {
                $q->whereNull('k.course_id');
            } else {
                $q->where('k.course_id', $filters['course_id']);
            }
        }
        if (! empty($filters['date_from'])) {
            $q->whereDate('k.clock_in', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->whereDate('k.clock_in', '<=', $filters['date_to']);
        }

        return $q;
    }

    public function query(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->orderByDesc('k.clock_in')
            ->paginate($perPage);
    }
}
