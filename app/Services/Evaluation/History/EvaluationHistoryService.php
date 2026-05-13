<?php

namespace App\Services\Evaluation\History;

use App\Enums\PerformanceLevel;
use App\Models\Evaluation;
use App\Models\EvaluationHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvaluationHistoryService
{
    public function getHistory(array $filters): LengthAwarePaginator
    {
        $query = Evaluation::with(['user', 'department', 'course', 'courseOnline', 'histories']);

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['course_type'])) {
            $query->where('course_type', $filters['course_type']);
        }
        if (!empty($filters['performance_level'])) {
            $query->where('performance_level', $filters['performance_level']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->latest()->paginate(20);
    }

    public function getById(int $evaluationId): Evaluation
    {
        return Evaluation::with(['user', 'department', 'course', 'courseOnline', 'histories'])->findOrFail($evaluationId);
    }

    public function getAnalytics(array $filters): array
    {
        $query = Evaluation::query();

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['course_type'])) {
            $query->where('course_type', $filters['course_type']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $total = (clone $query)->count();
        $avgScore = $total > 0 ? round((clone $query)->avg('total_score'), 2) : 0;

        // Performance distribution
        $distribution = [];
        $levelCounts = (clone $query)->selectRaw('performance_level, count(*) as cnt')
            ->groupBy('performance_level')
            ->pluck('cnt', 'performance_level')
            ->toArray();

        foreach ([1, 2, 3, 4] as $level) {
            $count = $levelCounts[$level] ?? 0;
            $distribution[] = array_merge(PerformanceLevel::getMetaByLevel($level), [
                'count'      => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ]);
        }

        // Monthly trends — last 12 months
        $driver      = \DB::connection()->getDriverName();
        $monthFormat = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $monthly = (clone $query)
            ->selectRaw("{$monthFormat} as month_key, COUNT(*) as count, ROUND(AVG(total_score), 2) as avg_score")
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupByRaw($monthFormat)
            ->orderBy('month_key')
            ->get()
            ->map(fn($r) => [
                'month'     => $r->month_key,
                'count'     => $r->count,
                'avg_score' => $r->avg_score,
            ])
            ->toArray();

        // Top 5 categories by average score
        $topCategories = EvaluationHistory::selectRaw('config_name, ROUND(AVG(score_given), 2) as avg_score')
            ->groupBy('config_name')
            ->orderByDesc('avg_score')
            ->limit(5)
            ->get()
            ->map(fn($r) => ['name' => $r->config_name, 'avg_score' => $r->avg_score])
            ->toArray();

        return [
            'total_evaluations'      => $total,
            'average_score'          => $avgScore,
            'performance_distribution' => $distribution,
            'monthly_trends'         => $monthly,
            'top_categories'         => $topCategories,
        ];
    }

    public function exportCsv(array $filters): StreamedResponse
    {
        $query = Evaluation::with(['user', 'department', 'course', 'courseOnline']);

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['course_type'])) {
            $query->where('course_type', $filters['course_type']);
        }
        if (!empty($filters['performance_level'])) {
            $query->where('performance_level', $filters['performance_level']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee', 'Department', 'Course', 'Course Type', 'Total Score', 'Performance Level', 'Date']);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $eval) {
                    $course = $eval->course_type === 'online'
                        ? optional($eval->courseOnline)->name
                        : optional($eval->course)->name;

                    fputcsv($handle, [
                        optional($eval->user)->name,
                        optional($eval->department)->name,
                        $course,
                        $eval->course_type,
                        $eval->total_score,
                        PerformanceLevel::getLabelByLevel($eval->performance_level ?? 4),
                        $eval->created_at?->format('Y-m-d'),
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="evaluations.csv"',
        ]);
    }

    public function exportSummaryCsv(array $filters): StreamedResponse
    {
        $query = Evaluation::query();

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $total = (clone $query)->count();

        $levelCounts = (clone $query)->selectRaw('performance_level, count(*) as cnt, ROUND(AVG(total_score),2) as avg_score')
            ->groupBy('performance_level')
            ->get()
            ->keyBy('performance_level');

        return response()->stream(function () use ($levelCounts, $total) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Performance Level', 'Count', 'Percentage', 'Avg Score']);

            foreach ([1, 2, 3, 4] as $level) {
                $row = $levelCounts[$level] ?? null;
                $count = $row?->cnt ?? 0;
                fputcsv($handle, [
                    PerformanceLevel::getLabelByLevel($level),
                    $count,
                    $total > 0 ? round(($count / $total) * 100, 1) . '%' : '0%',
                    $row?->avg_score ?? 0,
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="evaluation-summary.csv"',
        ]);
    }
}
