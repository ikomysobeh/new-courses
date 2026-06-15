<?php

namespace App\Services\Reporting\Export;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportingCsvExportService
{
    public function exportUserCourseDaily(array $filters = []): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user-course-daily-' . now()->toDateString() . '.csv"',
            'X-Accel-Buffering'   => 'no',
        ];

        return response()->stream(function () use ($filters) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['user_id', 'user_name', 'course_id', 'course_name', 'report_date', 'sessions', 'active_seconds', 'content_completed', 'progress_pct']);

            DB::table('reporting_user_course_daily as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->join('course_onlines as c', 'c.id', '=', 'r.course_online_id')
                ->select('r.user_id', 'u.name as user_name', 'r.course_online_id', 'c.name as course_name', 'r.report_date', 'r.sessions_count', 'r.active_playback_time', 'r.content_items_completed', 'r.course_progress_pct')
                ->when(! empty($filters['date_from']),        fn ($q) => $q->where('r.report_date', '>=', $filters['date_from']))
                ->when(! empty($filters['date_to']),          fn ($q) => $q->where('r.report_date', '<=', $filters['date_to']))
                ->when(! empty($filters['user_id']),          fn ($q) => $q->where('r.user_id', $filters['user_id']))
                ->when(! empty($filters['course_online_id']), fn ($q) => $q->where('r.course_online_id', $filters['course_online_id']))
                ->when(! empty($filters['department_id']),    fn ($q) => $q->where('r.department_id', $filters['department_id']))
                ->orderBy('r.report_date')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->user_id,
                            $row->user_name,
                            $row->course_online_id,
                            $row->course_name,
                            $row->report_date,
                            $row->sessions_count,
                            $row->active_playback_time,
                            $row->content_items_completed,
                            $row->course_progress_pct,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    public function exportDepartmentCourseDaily(array $filters = []): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="department-course-daily-' . now()->toDateString() . '.csv"',
            'X-Accel-Buffering'   => 'no',
        ];

        return response()->stream(function () use ($filters) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['department_id', 'department_name', 'course_id', 'course_name', 'report_date', 'enrolled', 'active', 'completed', 'avg_progress', 'total_active_seconds']);

            DB::table('reporting_department_course_daily as r')
                ->join('departments as d', 'd.id', '=', 'r.department_id')
                ->join('course_onlines as c', 'c.id', '=', 'r.course_online_id')
                ->select('r.*', 'd.name as department_name', 'c.name as course_name')
                ->when(! empty($filters['date_from']),        fn ($q) => $q->where('r.report_date', '>=', $filters['date_from']))
                ->when(! empty($filters['date_to']),          fn ($q) => $q->where('r.report_date', '<=', $filters['date_to']))
                ->when(! empty($filters['department_id']),    fn ($q) => $q->where('r.department_id', $filters['department_id']))
                ->when(! empty($filters['course_online_id']), fn ($q) => $q->where('r.course_online_id', $filters['course_online_id']))
                ->orderBy('r.report_date')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->department_id,
                            $row->department_name,
                            $row->course_online_id,
                            $row->course_name,
                            $row->report_date,
                            $row->enrolled_users,
                            $row->active_users,
                            $row->completed_users,
                            $row->avg_progress_percentage,
                            $row->total_active_seconds,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    public function exportSessionFact(array $filters = []): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="session-fact-' . now()->toDateString() . '.csv"',
            'X-Accel-Buffering'   => 'no',
        ];

        return response()->stream(function () use ($filters) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['session_id', 'user_id', 'user_name', 'course_id', 'course_name', 'session_date', 'active_seconds', 'completion_pct', 'attention_score', 'is_suspicious']);

            DB::table('reporting_learning_sessions_fact as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->join('course_onlines as c', 'c.id', '=', 'r.course_online_id')
                ->select('r.session_id', 'r.user_id', 'u.name as user_name', 'r.course_online_id', 'c.name as course_name', 'r.session_date', 'r.active_playback_time', 'r.completion_percentage', 'r.attention_score', 'r.is_suspicious')
                ->when(! empty($filters['date_from']),        fn ($q) => $q->where('r.session_date', '>=', $filters['date_from']))
                ->when(! empty($filters['date_to']),          fn ($q) => $q->where('r.session_date', '<=', $filters['date_to']))
                ->when(! empty($filters['user_id']),          fn ($q) => $q->where('r.user_id', $filters['user_id']))
                ->when(! empty($filters['course_online_id']), fn ($q) => $q->where('r.course_online_id', $filters['course_online_id']))
                ->when(! empty($filters['department_id']),    fn ($q) => $q->where('r.department_id', $filters['department_id']))
                ->when(isset($filters['is_suspicious']),      fn ($q) => $q->where('r.is_suspicious', (int) $filters['is_suspicious']))
                ->orderBy('r.session_date')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->session_id,
                            $row->user_id,
                            $row->user_name,
                            $row->course_online_id,
                            $row->course_name,
                            $row->session_date,
                            $row->active_playback_time,
                            $row->completion_percentage,
                            $row->attention_score,
                            $row->is_suspicious,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }

    public function exportKpiOverview(array $filters = []): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="kpi-overview-' . now()->toDateString() . '.csv"',
            'X-Accel-Buffering'   => 'no',
        ];

        return response()->stream(function () use ($filters) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['session_date', 'sessions', 'active_seconds', 'avg_completion_pct', 'avg_attention_score', 'suspicious_sessions']);

            DB::table('reporting_learning_sessions_fact')
                ->selectRaw('session_date, COUNT(*) as sessions, SUM(active_playback_time) as active_seconds, AVG(completion_percentage) as avg_completion_pct, AVG(attention_score) as avg_attention_score, SUM(is_suspicious) as suspicious_sessions')
                ->when(! empty($filters['date_from']), fn ($q) => $q->where('session_date', '>=', $filters['date_from']))
                ->when(! empty($filters['date_to']),   fn ($q) => $q->where('session_date', '<=', $filters['date_to']))
                ->groupBy('session_date')
                ->orderBy('session_date')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->session_date,
                            $row->sessions,
                            $row->active_seconds,
                            round($row->avg_completion_pct, 2),
                            round($row->avg_attention_score, 1),
                            $row->suspicious_sessions,
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
