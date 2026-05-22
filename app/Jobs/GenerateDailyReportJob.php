<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserCourseProgress;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateDailyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $reportDate = Carbon::yesterday()->toDateString();

        $rows = DB::table('reporting_learning_sessions_fact')
            ->select(
                'user_id',
                'course_online_id',
                DB::raw('COUNT(*) as sessions_count'),
                DB::raw('SUM(active_playback_time) as active_playback_time'),
                DB::raw('SUM(content_completed) as content_items_completed')
            )
            ->where('session_date', $reportDate)
            ->groupBy('user_id', 'course_online_id')
            ->get();

        $upserted = 0;

        foreach ($rows as $row) {
            $progressPct = UserCourseProgress::where('user_id', $row->user_id)
                ->where('course_online_id', $row->course_online_id)
                ->value('progress_percentage') ?? 0;

            $departmentId = User::where('id', $row->user_id)->value('department_id');

            DB::table('reporting_user_course_daily')->upsert(
                [
                    [
                        'user_id'                => $row->user_id,
                        'course_online_id'       => $row->course_online_id,
                        'department_id'          => $departmentId,
                        'report_date'            => $reportDate,
                        'sessions_count'         => $row->sessions_count,
                        'active_playback_time'   => $row->active_playback_time,
                        'content_items_completed' => $row->content_items_completed,
                        'course_progress_pct'    => $progressPct,
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ],
                ],
                ['user_id', 'course_online_id', 'report_date'],
                [
                    'sessions_count',
                    'active_playback_time',
                    'content_items_completed',
                    'course_progress_pct',
                    'updated_at',
                ]
            );

            $upserted++;
        }

        Log::info("GenerateDailyReportJob completed — {$upserted} rows upserted for {$reportDate}.");
    }
}
