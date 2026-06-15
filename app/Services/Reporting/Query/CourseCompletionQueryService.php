<?php

namespace App\Services\Reporting\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Live/traditional course completion report.
 * Source: course_completions (joined to users, courses) enriched with
 * the matching registration's registered_at so we can show time-to-complete.
 */
class CourseCompletionQueryService
{
    public function baseQuery(array $filters = []): Builder
    {
        $q = DB::table('course_completions as cc')
            ->join('users as u', 'u.id', '=', 'cc.user_id')
            ->join('courses as c', 'c.id', '=', 'cc.course_id')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->leftJoin('course_registrations as r', function ($join) {
                $join->on('r.user_id', '=', 'cc.user_id')
                     ->on('r.course_id', '=', 'cc.course_id');
            })
            ->select(
                'cc.id',
                'cc.user_id',
                'u.name as user_name',
                'u.email as user_email',
                'd.id as department_id',
                'd.name as department_name',
                'cc.course_id',
                'c.name as course_name',
                'r.registered_at',
                'cc.completed_at',
                'cc.rating',
                'cc.feedback'
            );

        if (! empty($filters['course_id'])) {
            $q->where('cc.course_id', $filters['course_id']);
        }
        if (! empty($filters['department_id'])) {
            $q->where('u.department_id', $filters['department_id']);
        }
        if (! empty($filters['user_id'])) {
            $q->where('cc.user_id', $filters['user_id']);
        }
        if (! empty($filters['date_from'])) {
            $q->whereDate('cc.completed_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->whereDate('cc.completed_at', '<=', $filters['date_to']);
        }

        return $q;
    }

    public function query(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->orderByDesc('cc.completed_at')
            ->paginate($perPage);
    }
}
