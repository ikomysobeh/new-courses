<?php

namespace App\Jobs;

use App\Models\LearningSession;
use App\Models\User;
use App\Services\OnlineCourse\User\LearningSessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanGhostSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(LearningSessionService $sessionService): void
    {
        $ghostSessions = LearningSession::whereNull('session_end')
            ->where(function ($q) {
                $q->where('last_progress_at', '<', now()->subMinutes(10))
                  ->orWhere(function ($q2) {
                      $q2->whereNull('last_progress_at')
                         ->where('session_start', '<', now()->subMinutes(10));
                  });
            })
            ->with('content')
            ->limit(100)
            ->get();

        $closed = 0;

        foreach ($ghostSessions as $session) {
            try {
                $this->closeGhostSession($session, $sessionService);
                $closed++;
            } catch (\Throwable $e) {
                // One bad session must not stop the other 99 in this batch
                // from being closed.
                Log::error('CleanGhostSessionsJob: failed to close session', [
                    'session_id' => $session->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        Log::info("CleanGhostSessionsJob: closed {$closed} ghost sessions.");
    }

    private function closeGhostSession(LearningSession $session, LearningSessionService $sessionService): void
    {
        $sessionEnd = $session->last_progress_at ?? $session->session_start;
        $wallClock  = (int) $sessionEnd->diffInSeconds($session->session_start);

        $fakeData = [
            'active_playback_time'  => $session->active_playback_time,
            'wall_clock_time'       => $wallClock,
            'completion_percentage' => $session->video_completion_percentage,
            'skip_count'            => $session->skip_count,
            'seek_count'            => $session->seek_count,
            'replay_count'          => $session->replay_count,
            'pause_count'           => $session->pause_count,
            'speed_changes'         => $session->speed_changes,
        ];

        $attention  = $sessionService->calculateAttentionScore($session, $session->content, $fakeData);
        $suspicious = $sessionService->isSuspicious($fakeData, $session->content?->duration);

        $session->update([
            'session_end'        => $sessionEnd,
            'wall_clock_seconds' => $wallClock,
            'attention_score'    => $attention,
            'is_suspicious'      => $suspicious ? 1 : 0,
        ]);

        $user = User::find($session->user_id);

        // updateOrInsert instead of insert: a fact row for this session_id may
        // already exist (overlapping job runs, or a session that went ghost,
        // resumed, and went ghost again) — the table enforces one row per
        // session_id, so a plain insert() would throw a duplicate-key error
        // and abort the rest of this batch.
        DB::table('reporting_learning_sessions_fact')->updateOrInsert(
            ['session_id' => $session->id],
            [
                'user_id'               => $session->user_id,
                'course_online_id'      => $session->course_online_id,
                'content_id'            => $session->content_id,
                'department_id'         => $user?->department_id,
                'session_date'          => $session->session_start->toDateString(),
                'active_playback_time'  => $session->active_playback_time,
                'wall_clock_seconds'    => $wallClock,
                'completion_percentage' => $session->video_completion_percentage,
                'attention_score'       => $attention,
                'is_suspicious'         => $suspicious ? 1 : 0,
                'skip_count'            => $session->skip_count,
                'seek_count'            => $session->seek_count,
                'replay_count'          => $session->replay_count,
                'pause_count'           => $session->pause_count,
                'content_completed'     => 0,
                'created_at'            => now(),
            ]
        );

        // Write activity log
        DB::table('activity_logs')->insert([
            'user_id'     => $session->user_id,
            'type'        => 'ghost_session_closed',
            'description' => 'Ghost session auto-closed',
            'properties'  => json_encode([
                'session_id' => $session->id,
                'reason'     => 'no_progress_10min',
            ]),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
