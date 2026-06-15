<?php

namespace App\Services\Reporting\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Live/traditional course registrations report.
 * Source: course_registrations (joined to users, courses).
 */
class CourseRegistrationQueryService
{
    public function baseQuery(array $filters = []): Builder
    {
        $q = DB::table('course_registrations as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->join('courses as c', 'c.id', '=', 'r.course_id')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->select(
                'r.id',
                'r.user_id',
                'u.name as user_name',
                'u.email as user_email',
                'd.id as department_id',
                'd.name as department_name',
                'r.course_id',
                'c.name as course_name',
                'r.status',
                'r.registered_at',
                'r.completed_at',
                'r.rating',
                'r.feedback'
            );

        if (! empty($filters['course_id'])) {
            $q->where('r.course_id', $filters['course_id']);
        }
        if (! empty($filters['department_id'])) {
            $q->where('u.department_id', $filters['department_id']);
        }
        if (! empty($filters['user_id'])) {
            $q->where('r.user_id', $filters['user_id']);
        }
        if (! empty($filters['status'])) {
            $q->where('r.status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $q->whereDate('r.registered_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->whereDate('r.registered_at', '<=', $filters['date_to']);
        }

        return $q;
    }

    public function query(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->orderByDesc('r.registered_at')
            ->paginate($perPage);
    }
}
