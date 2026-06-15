<?php

namespace App\Services\Reporting\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SessionFactQueryService
{
    public function query(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $q = DB::table('reporting_learning_sessions_fact as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->join('course_onlines as c', 'c.id', '=', 'r.course_online_id')
            ->leftJoin('departments as d', 'd.id', '=', 'r.department_id')
            ->select(
                'r.*',
                'u.name as user_name',
                'c.name as course_name',
                'd.name as department_name'
            );

        if (! empty($filters['date_from'])) {
            $q->where('r.session_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->where('r.session_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['user_id'])) {
            $q->where('r.user_id', $filters['user_id']);
        }
        if (! empty($filters['course_online_id'])) {
            $q->where('r.course_online_id', $filters['course_online_id']);
        }
        if (! empty($filters['department_id'])) {
            $q->where('r.department_id', $filters['department_id']);
        }
        if (isset($filters['is_suspicious'])) {
            $q->where('r.is_suspicious', (int) $filters['is_suspicious']);
        }

        return $q->orderByDesc('r.session_date')->paginate($perPage);
    }
}
