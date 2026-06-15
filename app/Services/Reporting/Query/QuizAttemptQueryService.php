<?php

namespace App\Services\Reporting\Query;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Quiz attempts report.
 * Source: quiz_attempts (joined to users, quizzes). A quiz may belong to a
 * traditional course, an online course, or a module.
 */
class QuizAttemptQueryService
{
    public function baseQuery(array $filters = []): Builder
    {
        $q = DB::table('quiz_attempts as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('quizzes as q', 'q.id', '=', 'a.quiz_id')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->select(
                'a.id',
                'a.user_id',
                'u.name as user_name',
                'u.email as user_email',
                'd.id as department_id',
                'd.name as department_name',
                'a.quiz_id',
                'q.title as quiz_title',
                'q.total_points',
                'q.pass_threshold',
                'a.attempt_number',
                'a.score',
                'a.total_score',
                'a.passed',
                'a.submitted_after_deadline',
                'a.started_at',
                'a.completed_at'
            );

        if (! empty($filters['quiz_id'])) {
            $q->where('a.quiz_id', $filters['quiz_id']);
        }
        if (! empty($filters['user_id'])) {
            $q->where('a.user_id', $filters['user_id']);
        }
        if (! empty($filters['department_id'])) {
            $q->where('u.department_id', $filters['department_id']);
        }
        // status: passed | failed | pending (pending = not yet completed)
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'passed') {
                $q->where('a.passed', 1)->whereNotNull('a.completed_at');
            } elseif ($filters['status'] === 'failed') {
                $q->where('a.passed', 0)->whereNotNull('a.completed_at');
            } elseif ($filters['status'] === 'pending') {
                $q->whereNull('a.completed_at');
            }
        }
        if (! empty($filters['date_from'])) {
            $q->whereDate('a.completed_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->whereDate('a.completed_at', '<=', $filters['date_to']);
        }

        return $q;
    }

    public function query(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->orderByDesc('a.completed_at')
            ->paginate($perPage);
    }
}
