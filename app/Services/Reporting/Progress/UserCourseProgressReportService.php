<?php

namespace App\Services\Reporting\Progress;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the enriched User Course Progress rows (online + traditional) used by
 * both the Excel export and the reporting snapshot aggregator.
 *
 * Each row is a plain array whose keys match the columns the Excel writer and
 * the snapshot table expect. Kept as one place so live export and the cached
 * table can never drift apart.
 */
class UserCourseProgressReportService
{
    public function __construct(
        protected LearningScoreCalculator $scores,
    ) {}

    /**
     * @param array $filters department_id, course_type, date_from, date_to,
     *                        status, user_id, course_id
     * @return Collection<int, array<string, mixed>>
     */
    public function buildRows(array $filters = []): Collection
    {
        $type = $filters['course_type'] ?? null;

        $rows = collect();

        if ($type === null || $type === 'online') {
            $rows = $rows->merge($this->buildOnline($filters));
        }
        if ($type === null || $type === 'traditional') {
            $rows = $rows->merge($this->buildTraditional($filters));
        }

        // Optional status filter (applied after normalization)
        if (! empty($filters['status'])) {
            $rows = $rows->filter(fn ($r) => $r['status'] === $filters['status'])->values();
        }

        return $rows->values();
    }

    // -------------------------------------------------------------------------
    // Online courses
    // -------------------------------------------------------------------------

    private function buildOnline(array $filters): Collection
    {
        $query = DB::table('course_online_assignments as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('course_onlines as c', 'c.id', '=', 'a.course_online_id')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->leftJoin('user_course_progress as p', function ($join) {
                $join->on('p.user_id', '=', 'a.user_id')
                     ->on('p.course_online_id', '=', 'a.course_online_id');
            })
            ->select([
                'a.user_id',
                'u.name as user_name',
                'u.department_id',
                'd.name as department_name',
                'c.id as course_id',
                'c.name as course_name',
                'c.deadline as course_deadline',
                'a.assigned_at',
                'p.progress_percentage',
                'p.status as progress_status',
                'p.started_at',
                'p.completed_at',
            ]);

        $this->applyCommonFilters($query, $filters, 'online');

        return $query->get()->map(function ($r) {
            $isCompleted = $r->progress_status === 'completed' || $r->completed_at !== null;
            $progress    = $isCompleted ? 100.0 : (float) ($r->progress_percentage ?? 0);

            $startedAt   = $r->started_at ? Carbon::parse($r->started_at) : null;
            $completedAt = $r->completed_at ? Carbon::parse($r->completed_at) : null;
            $deadline    = $r->course_deadline ? Carbon::parse($r->course_deadline) : null;
            // Online "course beginning date" mirrors the old report: assignment date.
            $beginning   = $r->assigned_at ? Carbon::parse($r->assigned_at) : null;

            $status = $this->normalizeStatus($isCompleted, $progress, $startedAt);

            return $this->assembleRow(
                courseType: 'online',
                userId: (int) $r->user_id,
                userName: $r->user_name,
                departmentId: $r->department_id ? (int) $r->department_id : null,
                departmentName: $r->department_name,
                courseId: (int) $r->course_id,
                courseName: $r->course_name,
                progress: $progress,
                isCompleted: $isCompleted,
                status: $status,
                startedAt: $startedAt,
                completedAt: $completedAt,
                deadline: $deadline,
                beginning: $beginning,
            );
        });
    }

    // -------------------------------------------------------------------------
    // Traditional courses
    // -------------------------------------------------------------------------

    private function buildTraditional(array $filters): Collection
    {
        $query = DB::table('course_assignments as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('courses as co', 'co.id', '=', 'a.course_id')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->leftJoin('course_availabilities as av', 'av.id', '=', 'a.course_availability_id')
            ->leftJoin('course_registrations as r', function ($join) {
                $join->on('r.user_id', '=', 'a.user_id')
                     ->on('r.course_id', '=', 'a.course_id');
            })
            ->leftJoin('course_completions as cc', function ($join) {
                $join->on('cc.user_id', '=', 'a.user_id')
                     ->on('cc.course_id', '=', 'a.course_id');
            })
            ->select([
                'a.user_id',
                'u.name as user_name',
                'u.department_id',
                'd.name as department_name',
                'co.id as course_id',
                'co.name as course_name',
                'a.assigned_at',
                'av.start_date as avail_start',
                'av.end_date as avail_end',
                'av.sessions as avail_sessions',
                'r.status as reg_status',
                'r.registered_at',
                'r.completed_at as reg_completed_at',
                'cc.completed_at as comp_completed_at',
            ]);

        $this->applyCommonFilters($query, $filters, 'traditional');

        return $query->get()->map(function ($r) {
            $completedAtRaw = $r->comp_completed_at ?? $r->reg_completed_at;
            $isCompleted    = $completedAtRaw !== null || $r->reg_status === 'completed';
            $completedAt    = $completedAtRaw ? Carbon::parse($completedAtRaw) : null;

            // Attendance-based progress: attended clockings / scheduled sessions
            $attended = (int) DB::table('clockings')
                ->where('user_id', $r->user_id)
                ->where('course_id', $r->course_id)
                ->whereNotNull('clock_out')
                ->count();

            $totalSessions = (int) ($r->avail_sessions ?? 0);

            if ($isCompleted) {
                $progress = 100.0;
            } elseif ($totalSessions > 0) {
                $progress = min(round(($attended / $totalSessions) * 100, 2), 100);
            } else {
                $progress = 0.0;
            }

            $startedAt = $r->registered_at ? Carbon::parse($r->registered_at) : null;
            if (! $startedAt) {
                $firstClock = DB::table('clockings')
                    ->where('user_id', $r->user_id)
                    ->where('course_id', $r->course_id)
                    ->whereNotNull('clock_in')
                    ->min('clock_in');
                $startedAt = $firstClock ? Carbon::parse($firstClock) : null;
            }

            $deadline  = $r->avail_end ? Carbon::parse($r->avail_end) : null;
            $beginning = $r->avail_start ? Carbon::parse($r->avail_start) : null;

            $status = $this->normalizeStatus($isCompleted, $progress, $startedAt);

            return $this->assembleRow(
                courseType: 'traditional',
                userId: (int) $r->user_id,
                userName: $r->user_name,
                departmentId: $r->department_id ? (int) $r->department_id : null,
                departmentName: $r->department_name,
                courseId: (int) $r->course_id,
                courseName: $r->course_name,
                progress: (float) $progress,
                isCompleted: $isCompleted,
                status: $status,
                startedAt: $startedAt,
                completedAt: $completedAt,
                deadline: $deadline,
                beginning: $beginning,
            );
        });
    }

    // -------------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------------

    /**
     * Apply the filters common to both pipelines. Column names differ per type.
     */
    private function applyCommonFilters($query, array $filters, string $courseType): void
    {
        if (! empty($filters['department_id'])) {
            $query->where('u.department_id', $filters['department_id']);
        }
        if (! empty($filters['user_id'])) {
            $query->where('a.user_id', $filters['user_id']);
        }
        if (! empty($filters['course_id'])) {
            $courseCol = $courseType === 'online' ? 'a.course_online_id' : 'a.course_id';
            $query->where($courseCol, $filters['course_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('a.assigned_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('a.assigned_at', '<=', $filters['date_to']);
        }
    }

    private function normalizeStatus(bool $isCompleted, float $progress, ?Carbon $startedAt): string
    {
        if ($isCompleted) {
            return 'completed';
        }
        if ($progress > 0 || $startedAt !== null) {
            return 'in_progress';
        }
        return 'not_started';
    }

    /**
     * Enrich with scores and build the final row array. Keys intentionally match
     * the old ExcelExportService so the writer stays a faithful copy.
     */
    private function assembleRow(
        string $courseType,
        int $userId,
        ?string $userName,
        ?int $departmentId,
        ?string $departmentName,
        int $courseId,
        ?string $courseName,
        float $progress,
        bool $isCompleted,
        string $status,
        ?Carbon $startedAt,
        ?Carbon $completedAt,
        ?Carbon $deadline,
        ?Carbon $beginning,
    ): array {
        $completionRate = $isCompleted ? 100.0 : 0.0;

        $attention = $this->scores->getAttentionScore($userId, $courseId, $courseType);
        $quiz      = $this->scores->getQuizScore($userId, $courseId, $courseType);

        $suspicious = 0;
        $totalSessions = 0;
        if ($courseType === 'online') {
            $sessionStats  = $this->scores->getSessionStats($userId, $courseId);
            $suspicious    = $sessionStats['suspicious'];
            $totalSessions = $sessionStats['total'];
        }

        $learningScore = round($this->scores->calculate(
            $completionRate,
            $progress,
            $attention,
            $quiz,
            $suspicious,
            $totalSessions,
            $courseType
        ), 1);

        $daysOverdue = $this->daysOverdue($deadline, $isCompleted);

        return [
            'user_id'                        => $userId,
            'user_name'                      => $userName ?? '',
            'department_id'                  => $departmentId,
            'department'                     => $departmentName ?? 'N/A',
            'course_type'                    => $courseType,
            'course_id'                      => $courseId,
            'course_name'                    => $courseName ?? '',
            'progress_percentage'            => $progress,
            'status'                         => $status,
            'completion_status'              => $this->completionLabel($status),
            'is_completed'                   => $isCompleted,
            'days_overdue'                   => $daysOverdue,
            'started_at'                     => $startedAt,
            'completed_at'                   => $completedAt,
            'deadline'                       => $deadline,
            'course_beginning_date'          => $beginning,
            // Pre-formatted strings for the Excel writer (m/d/Y like the old file)
            'started_date'                   => $startedAt ? $startedAt->format('m/d/Y') : '',
            'completion_date'                => $completedAt ? $completedAt->format('m/d/Y') : '',
            'course_beginning_date_formatted'=> $beginning ? $beginning->format('m/d/Y') : '',
            'attention_score'                => $attention,
            'quiz_score'                     => $quiz,
            'completion_rate'                => $completionRate,
            'learning_score'                 => $learningScore,
            'score_band'                     => $this->scoreBand($learningScore),
            'compliance_status'              => $this->complianceStatus($deadline, $isCompleted, $learningScore),
        ];
    }

    private function daysOverdue(?Carbon $deadline, bool $isCompleted): ?int
    {
        if (! $deadline || $isCompleted) {
            return null;
        }

        $now      = Carbon::now()->startOfDay();
        $deadline = $deadline->copy()->startOfDay();

        if ($deadline->gte($now)) {
            return null;
        }

        return (int) abs($now->diffInDays($deadline));
    }

    private function completionLabel(string $status): string
    {
        return match ($status) {
            'completed'   => 'Completed',
            'in_progress' => 'In Progress',
            default       => 'Not Started',
        };
    }

    private function scoreBand(float $score): string
    {
        if ($score >= 85) {
            return 'Excellent';
        }
        if ($score >= 70) {
            return 'Good';
        }
        return 'Needs Attention';
    }

    private function complianceStatus(?Carbon $deadline, bool $isCompleted, float $learningScore): string
    {
        if ($isCompleted) {
            return $learningScore < 70 ? 'Non-Compliant' : 'Compliant';
        }
        if (! $deadline) {
            return 'Compliant';
        }
        if ($deadline->isPast()) {
            return 'Non-Compliant';
        }

        $daysUntil = Carbon::now()->diffInDays($deadline, false);
        if ($daysUntil >= 0 && $daysUntil <= 7) {
            return 'At Risk';
        }

        return 'Compliant';
    }
}
