<?php

namespace App\Services\Reporting\Aggregation;

use App\Models\LearningSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LearningSessionFactAggregatorService
{
    /**
     * Upsert a single closed learning session into the fact table.
     */
    public function upsertFromClosedSession(int $learningSessionId): void
    {
        $session = LearningSession::with('user')->findOrFail($learningSessionId);

        $row = [
            'session_id'            => $session->id,
            'user_id'               => $session->user_id,
            'course_online_id'      => $session->course_online_id,
            'content_id'            => $session->content_id,
            'department_id'         => $session->user?->department_id,
            'session_date'          => Carbon::parse($session->session_start)->toDateString(),
            'active_playback_time'  => $session->active_playback_time ?? 0,
            'wall_clock_seconds'    => $session->wall_clock_seconds,
            'completion_percentage' => $session->video_completion_percentage ?? 0,
            'attention_score'       => $session->attention_score,
            'is_suspicious'         => $session->is_suspicious ?? 0,
            'skip_count'            => $session->skip_count ?? 0,
            'seek_count'            => $session->seek_count ?? 0,
            'replay_count'          => $session->replay_count ?? 0,
            'pause_count'           => $session->pause_count ?? 0,
            'content_completed'     => 0,
            'created_at'            => now(),
        ];

        DB::table('reporting_learning_sessions_fact')->upsert(
            [$row],
            ['session_id'],
            [
                'active_playback_time',
                'wall_clock_seconds',
                'completion_percentage',
                'attention_score',
                'is_suspicious',
                'skip_count',
                'seek_count',
                'replay_count',
                'pause_count',
                'content_completed',
            ]
        );
    }

    /**
     * Backfill all closed sessions for a given date.
     * Returns number of rows written.
     */
    public function backfillByDate(Carbon $date): int
    {
        $dateStr = $date->toDateString();

        $sessions = LearningSession::with('user')
            ->whereDate('session_start', $dateStr)
            ->whereNotNull('session_end')
            ->get();

        foreach ($sessions as $session) {
            $this->upsertFromClosedSession($session->id);
        }

        return $sessions->count();
    }

    /**
     * Backfill all closed sessions for a date range.
     */
    public function backfillDateRange(Carbon $from, Carbon $to): int
    {
        $total  = 0;
        $cursor = $from->copy()->startOfDay();
        $end    = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $total += $this->backfillByDate($cursor->copy());
            $cursor->addDay();
        }

        return $total;
    }
}
