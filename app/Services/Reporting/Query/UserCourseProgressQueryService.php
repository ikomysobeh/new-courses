<?php

namespace App\Services\Reporting\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Compliance-oriented user course progress report (online courses).
 * Source: user_course_progress joined to course_onlines (for the deadline),
 * users and departments. Derived fields (days_overdue, compliance_status,
 * score_band) are computed in UserCourseProgressResource.
 */
class UserCourseProgressQueryService
{
    public function baseQuery(array $filters = []): Builder
    {
        $q = DB::table('user_course_progress as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->join('course_onlines as c', 'c.id', '=', 'p.course_online_id')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->select(
                'p.id',
                'p.user_id',
                'u.name as user_name',
                'u.email as user_email',
                'd.id as department_id',
                'd.name as department_name',
                'p.course_online_id',
                'c.name as course_name',
                'c.deadline as course_deadline',
                'p.progress_percentage',
                'p.status',
                'p.total_content_items',
                'p.completed_content_items',
                'p.started_at',
                'p.completed_at',
                'p.last_accessed_at'
            );

        if (! empty($filters['course_online_id'])) {
            $q->where('p.course_online_id', $filters['course_online_id']);
        }
        if (! empty($filters['department_id'])) {
            $q->where('u.department_id', $filters['department_id']);
        }
        if (! empty($filters['user_id'])) {
            $q->where('p.user_id', $filters['user_id']);
        }
        if (! empty($filters['status'])) {
            $q->where('p.status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $q->whereDate('p.started_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->whereDate('p.started_at', '<=', $filters['date_to']);
        }

        return $q;
    }

    public function query(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->orderByDesc('p.last_accessed_at')
            ->paginate($perPage);
    }
}
