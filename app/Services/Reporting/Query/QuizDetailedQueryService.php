<?php

namespace App\Services\Reporting\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Question-level quiz export source.
 * One row per answered question per attempt: shows the question, the user's
 * answer, the correct answer, and points earned. Used by the detailed CSV export.
 */
class QuizDetailedQueryService
{
    public function baseQuery(array $filters = []): Builder
    {
        $q = DB::table('quiz_answers as ans')
            ->join('quiz_attempts as a', 'a.id', '=', 'ans.quiz_attempt_id')
            ->join('quiz_questions as qq', 'qq.id', '=', 'ans.quiz_question_id')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('quizzes as q', 'q.id', '=', 'a.quiz_id')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->select(
                'a.id as attempt_id',
                'a.user_id',
                'u.name as user_name',
                'u.email as user_email',
                'd.name as department_name',
                'a.quiz_id',
                'q.title as quiz_title',
                'a.attempt_number',
                'a.passed',
                'a.completed_at',
                'qq.id as question_id',
                'qq.order as question_order',
                'qq.question_text',
                'qq.type as question_type',
                'qq.points as question_points',
                'qq.correct_answer',
                'ans.answer as user_answer',
                'ans.is_correct',
                'ans.points_earned'
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
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'passed') {
                $q->where('a.passed', 1);
            } elseif ($filters['status'] === 'failed') {
                $q->where('a.passed', 0)->whereNotNull('a.completed_at');
            }
        }
        if (! empty($filters['date_from'])) {
            $q->whereDate('a.completed_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->whereDate('a.completed_at', '<=', $filters['date_to']);
        }

        return $q->orderBy('a.id')->orderBy('qq.order');
    }
}
