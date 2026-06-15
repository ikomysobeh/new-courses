<?php

namespace App\Services\Reporting\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DepartmentDailyQueryService
{
    public function query(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $q = DB::table('reporting_department_course_daily as r')
            ->join('departments as d', 'd.id', '=', 'r.department_id')
            ->join('course_onlines as c', 'c.id', '=', 'r.course_online_id')
            ->select(
                'r.*',
                'd.name as department_name',
                'c.name as course_name'
            );

        if (! empty($filters['date_from'])) {
            $q->where('r.report_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->where('r.report_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['department_id'])) {
            $q->where('r.department_id', $filters['department_id']);
        }
        if (! empty($filters['course_online_id'])) {
            $q->where('r.course_online_id', $filters['course_online_id']);
        }

        return $q->orderByDesc('r.report_date')->paginate($perPage);
    }
}
