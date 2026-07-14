<?php

namespace App\Services\Reporting\Export;

use App\Services\Reporting\Query\AttendanceQueryService;
use App\Services\Reporting\Query\CourseCompletionQueryService;
use App\Services\Reporting\Query\CourseRegistrationQueryService;
use App\Services\Reporting\Query\EvaluationDepartmentQueryService;
use App\Services\Reporting\Query\QuizAttemptQueryService;
use App\Services\Reporting\Query\QuizDetailedQueryService;
use App\Services\Reporting\Query\UserCourseProgressQueryService;
use App\Services\Reporting\Query\UserPerformanceQueryService;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streamed CSV exports for the live-course, quiz, user-performance,
 * compliance and evaluation reports. Reuses the report query services so the
 * exported data matches the JSON endpoints exactly.
 */
class ExtendedReportCsvExportService
{
    public function __construct(
        protected CourseRegistrationQueryService   $registrations,
        protected AttendanceQueryService           $attendance,
        protected CourseCompletionQueryService     $completions,
        protected QuizAttemptQueryService          $quizAttempts,
        protected QuizDetailedQueryService         $quizDetailed,
        protected UserPerformanceQueryService      $performance,
        protected UserCourseProgressQueryService   $progress,
        protected EvaluationDepartmentQueryService $evaluations,
    ) {}

    // ------------------------------------------------------------------
    // Live / traditional course reports
    // ------------------------------------------------------------------

    public function exportCourseRegistrations(array $filters = []): StreamedResponse
    {
        return $this->stream('course-registrations',
            ['user_id', 'user_name', 'email', 'department', 'course_id', 'course_name', 'status', 'registered_at', 'completed_at', 'rating', 'feedback'],
            fn ($handle) => $this->registrations->baseQuery($filters)->orderByDesc('r.registered_at')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->user_id, $row->user_name, $row->user_email, $row->department_name,
                        $row->course_id, $row->course_name, $row->status,
                        $row->registered_at, $row->completed_at, $row->rating, $row->feedback,
                    ]);
                }
            })
        );
    }

    public function exportAttendance(array $filters = []): StreamedResponse
    {
        return $this->stream('attendance',
            ['user_id', 'user_name', 'email', 'department', 'course_id', 'course_name', 'clock_in', 'clock_out', 'duration_minutes', 'rating', 'comment'],
            fn ($handle) => $this->attendance->baseQuery($filters)->orderByDesc('k.clock_in')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->user_id, $row->user_name, $row->user_email, $row->department_name,
                        $row->course_id, $row->course_name ?? 'General Attendance',
                        $row->clock_in, $row->clock_out, $row->duration_in_minutes, $row->rating, $row->comment,
                    ]);
                }
            })
        );
    }

    public function exportCourseCompletion(array $filters = []): StreamedResponse
    {
        return $this->stream('course-completion',
            ['user_id', 'user_name', 'email', 'department', 'course_id', 'course_name', 'registered_at', 'completed_at', 'days_to_complete', 'rating', 'feedback'],
            fn ($handle) => $this->completions->baseQuery($filters)->orderByDesc('cc.completed_at')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    $days = ($row->registered_at && $row->completed_at)
                        ? Carbon::parse($row->registered_at)->diffInDays(Carbon::parse($row->completed_at))
                        : null;
                    fputcsv($handle, [
                        $row->user_id, $row->user_name, $row->user_email, $row->department_name,
                        $row->course_id, $row->course_name, $row->registered_at, $row->completed_at,
                        $days, $row->rating, $row->feedback,
                    ]);
                }
            })
        );
    }

    // ------------------------------------------------------------------
    // Quiz reports
    // ------------------------------------------------------------------

    public function exportQuizAttempts(array $filters = []): StreamedResponse
    {
        return $this->stream('quiz-attempts',
            ['user_id', 'user_name', 'email', 'department', 'quiz_id', 'quiz_title', 'attempt_number', 'score', 'total_points', 'percentage', 'pass_threshold', 'passed', 'completed_at'],
            fn ($handle) => $this->quizAttempts->baseQuery($filters)->orderByDesc('a.completed_at')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    $achieved = $row->total_score ?? $row->score;
                    $pct      = $row->total_points > 0 ? round($achieved / $row->total_points * 100, 2) : null;
                    fputcsv($handle, [
                        $row->user_id, $row->user_name, $row->user_email, $row->department_name,
                        $row->quiz_id, $row->quiz_title, $row->attempt_number, $achieved,
                        $row->total_points, $pct, $row->pass_threshold,
                        $row->passed ? 'yes' : 'no', $row->completed_at,
                    ]);
                }
            })
        );
    }

    public function exportQuizDetailed(array $filters = []): StreamedResponse
    {
        return $this->stream('quiz-detailed',
            ['attempt_id', 'user_id', 'user_name', 'department', 'quiz_id', 'quiz_title', 'attempt_number', 'question_order', 'question_text', 'question_type', 'question_points', 'correct_answer', 'user_answer', 'is_correct', 'points_earned', 'completed_at'],
            fn ($handle) => $this->quizDetailed->baseQuery($filters)->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    $correct = $row->correct_answer;
                    // correct_answer is stored as JSON; flatten to a readable string
                    $decoded = json_decode($correct ?? '', true);
                    if (is_array($decoded)) {
                        $correct = implode(' | ', array_map(fn ($v) => is_scalar($v) ? $v : json_encode($v), $decoded));
                    }
                    fputcsv($handle, [
                        $row->attempt_id, $row->user_id, $row->user_name, $row->department_name,
                        $row->quiz_id, $row->quiz_title, $row->attempt_number, $row->question_order,
                        $row->question_text, $row->question_type, $row->question_points,
                        $correct, $row->user_answer,
                        $row->is_correct === null ? '' : ($row->is_correct ? 'yes' : 'no'),
                        $row->points_earned, $row->completed_at,
                    ]);
                }
            })
        );
    }

    // ------------------------------------------------------------------
    // User performance & compliance
    // ------------------------------------------------------------------

    public function exportUserPerformance(array $filters = []): StreamedResponse
    {
        return $this->stream('user-performance',
            ['user_id', 'user_name', 'email', 'department', 'total_assignments', 'completed_courses', 'in_progress_courses', 'completion_rate', 'progress', 'learning_time_minutes', 'avg_progress', 'sessions_count', 'total_active_seconds', 'avg_attention', 'suspicious_sessions', 'quiz_attempts', 'quiz_passed', 'avg_quiz_pct'],
            fn ($handle) => $this->performance->baseQuery($filters)->orderByDesc('avg_progress')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    $completionRate = $row->total_assignments > 0
                        ? round($row->completed_courses / $row->total_assignments * 100, 2)
                        : 0;
                    $learningMinutes = (int) round(((int) $row->total_active_seconds) / 60);
                    fputcsv($handle, [
                        $row->user_id, $row->user_name, $row->user_email, $row->department_name,
                        $row->total_assignments, $row->completed_courses, $row->in_progress_courses,
                        $completionRate, round((float) $row->avg_progress, 2), $learningMinutes,
                        round((float) $row->avg_progress, 2),
                        $row->sessions_count, $row->total_active_seconds,
                        round((float) $row->avg_attention, 1), $row->suspicious_sessions,
                        $row->quiz_attempts_count, $row->quiz_passed_count, round((float) $row->avg_quiz_pct, 2),
                    ]);
                }
            })
        );
    }

    public function exportUserCourseProgress(array $filters = []): StreamedResponse
    {
        return $this->stream('user-course-progress',
            ['user_id', 'user_name', 'email', 'department', 'course_id', 'course_name', 'progress_pct', 'status', 'deadline', 'days_overdue', 'compliance_status', 'started_at', 'completed_at'],
            fn ($handle) => $this->progress->baseQuery($filters)->orderByDesc('p.last_accessed_at')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    $isDone      = $row->status === 'completed';
                    $daysOverdue = 0;
                    $compliance  = 'on_track';

                    if ($isDone) {
                        $compliance = 'compliant';
                    } elseif ($row->course_deadline) {
                        $deadline = Carbon::parse($row->course_deadline);
                        if ($deadline->isPast()) {
                            $daysOverdue = (int) $deadline->diffInDays(Carbon::now());
                            $compliance  = 'non_compliant';
                        } elseif ($deadline->lessThanOrEqualTo(Carbon::now()->addDays(7))) {
                            $compliance = 'at_risk';
                        }
                    }

                    fputcsv($handle, [
                        $row->user_id, $row->user_name, $row->user_email, $row->department_name,
                        $row->course_online_id, $row->course_name,
                        round((float) $row->progress_percentage, 2), $row->status,
                        $row->course_deadline, $daysOverdue, $compliance,
                        $row->started_at, $row->completed_at,
                    ]);
                }
            })
        );
    }

    // ------------------------------------------------------------------
    // Evaluation-based department performance
    // ------------------------------------------------------------------

    public function exportEvaluationDepartment(array $filters = []): StreamedResponse
    {
        $rows = $this->evaluations->flatRows($filters);

        return $this->stream('evaluation-department-performance',
            ['department_id', 'department_name', 'rank', 'user_id', 'user_name', 'avg_score', 'eval_count'],
            function ($handle) use ($rows) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row['department_id'], $row['department_name'], $row['rank'],
                        $row['user_id'], $row['user_name'], $row['avg_score'], $row['eval_count'],
                    ]);
                }
            }
        );
    }

    // ------------------------------------------------------------------
    // Shared streaming helper
    // ------------------------------------------------------------------

    private function stream(string $name, array $header, callable $writeRows): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $name . '-' . now()->toDateString() . '.csv"',
            'X-Accel-Buffering'   => 'no',
        ];

        return response()->stream(function () use ($header, $writeRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $header);
            $writeRows($handle);
            fclose($handle);
        }, 200, $headers);
    }
}
