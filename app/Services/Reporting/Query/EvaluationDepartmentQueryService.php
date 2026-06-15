<?php

namespace App\Services\Reporting\Query;

use Illuminate\Support\Facades\DB;

/**
 * Department performance based on evaluation scores.
 * Ranks users within each department by their average evaluation total_score
 * and returns the top and bottom performers per department, plus overall stats.
 */
class EvaluationDepartmentQueryService
{
    /** Number of top / bottom performers returned per department. */
    private const PERFORMER_LIMIT = 3;

    /**
     * Per-user average evaluation score, grouped by department.
     * Returns a collection of rows: department_id, department_name, user_id,
     * user_name, avg_score, eval_count.
     */
    private function userAverages(array $filters = [])
    {
        return DB::table('evaluations as e')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->join('departments as d', 'd.id', '=', 'e.department_id')
            ->selectRaw('e.department_id, d.name as department_name, e.user_id, u.name as user_name,
                AVG(e.total_score) as avg_score, COUNT(*) as eval_count')
            ->when(! empty($filters['department_id']), fn ($q) => $q->where('e.department_id', $filters['department_id']))
            ->when(! empty($filters['course_type']), fn ($q) => $q->where('e.course_type', $filters['course_type']))
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('e.created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($q) => $q->whereDate('e.created_at', '<=', $filters['date_to']))
            ->groupBy('e.department_id', 'd.name', 'e.user_id', 'u.name')
            ->get();
    }

    /**
     * Nested structure for the JSON endpoint:
     * {
     *   summary: { departments, users_evaluated, overall_avg_score, highest_dept_avg },
     *   departments: [ { department_id, department_name, users_evaluated, avg_score,
     *                    top_performers: [...], needs_support: [...] } ]
     * }
     */
    public function generate(array $filters = []): array
    {
        $rows = $this->userAverages($filters);

        $departments = [];
        $deptAverages = [];

        foreach ($rows->groupBy('department_id') as $deptId => $users) {
            $sorted = $users->sortByDesc('avg_score')->values();

            $map = fn ($u, $rank) => [
                'rank'        => $rank,
                'user_id'     => (int) $u->user_id,
                'user_name'   => $u->user_name,
                'avg_score'   => round((float) $u->avg_score, 2),
                'eval_count'  => (int) $u->eval_count,
            ];

            $top = $sorted->take(self::PERFORMER_LIMIT)
                ->values()
                ->map(fn ($u, $i) => $map($u, $i + 1))
                ->all();

            // Bottom performers (lowest scores), only when more users than the top slice
            $bottom = [];
            if ($sorted->count() > self::PERFORMER_LIMIT) {
                $bottom = $sorted->reverse()->take(self::PERFORMER_LIMIT)
                    ->values()
                    ->map(fn ($u, $i) => $map($u, $i + 1))
                    ->all();
            }

            $deptAvg = round((float) $users->avg('avg_score'), 2);
            $deptAverages[] = $deptAvg;

            $departments[] = [
                'department_id'   => (int) $deptId,
                'department_name' => $users->first()->department_name,
                'users_evaluated' => $users->count(),
                'avg_score'       => $deptAvg,
                'top_performers'  => $top,
                'needs_support'   => $bottom,
            ];
        }

        // Sort departments by average score descending
        usort($departments, fn ($a, $b) => $b['avg_score'] <=> $a['avg_score']);

        return [
            'summary' => [
                'departments'      => count($departments),
                'users_evaluated'  => $rows->pluck('user_id')->unique()->count(),
                'overall_avg_score'=> $rows->count() ? round((float) $rows->avg('avg_score'), 2) : 0,
                'highest_dept_avg' => $deptAverages ? max($deptAverages) : 0,
            ],
            'departments' => $departments,
        ];
    }

    /**
     * Flat per-user rows for CSV export (one row per evaluated user).
     */
    public function flatRows(array $filters = []): array
    {
        $rows = $this->userAverages($filters);
        $out  = [];

        foreach ($rows->groupBy('department_id') as $users) {
            $sorted = $users->sortByDesc('avg_score')->values();
            foreach ($sorted as $rank => $u) {
                $out[] = [
                    'department_id'   => (int) $u->department_id,
                    'department_name' => $u->department_name,
                    'user_id'         => (int) $u->user_id,
                    'user_name'       => $u->user_name,
                    'rank'            => $rank + 1,
                    'avg_score'       => round((float) $u->avg_score, 2),
                    'eval_count'      => (int) $u->eval_count,
                ];
            }
        }

        return $out;
    }
}
