<?php

namespace App\Services\OnlineCourse\User;

use App\Models\AttentionScoreConfig;
use App\Models\CourseOnlineAssignment;
use App\Models\LearningSession;
use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\UserCourseProgress;
use App\Services\AttentionScore\AttentionScoreConfigService;
use App\Services\AttentionScore\AttentionScoreEngine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LearningSessionService
{
    public function __construct(
        private readonly AttentionScoreEngine $attentionScoreEngine,
        private readonly AttentionScoreConfigService $attentionScoreConfigService,
    ) {}

    public function startSession(int $userId, int $courseId, int $contentId, string $type): array
    {
        // Verify user is assigned to course
        $assigned = CourseOnlineAssignment::where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->exists();

        if (!$assigned) {
            abort(403, 'Not assigned to this course.');
        }

        // Load existing content progress
        $contentProgress = UserContentProgress::where('user_id', $userId)
            ->where('content_id', $contentId)
            ->first();

        if ($contentProgress && $contentProgress->is_completed) {
            abort(403, 'Content already completed.');
        }

        // Look for open session
        $openSession = LearningSession::where('user_id', $userId)
            ->where('content_id', $contentId)
            ->whereNull('session_end')
            ->first();

        if ($openSession) {
            $lastActivity = $openSession->last_progress_at ?? $openSession->session_start;

            if ($lastActivity->gt(now()->subMinutes(5))) {
                // Recent session — return existing
                return [
                    'session_id'      => $openSession->id,
                    'resume_position' => (float) ($contentProgress?->playback_position ?? 0),
                    'is_completed'    => false,
                ];
            }

            if ($lastActivity->lt(now()->subMinutes(10))) {
                // Ghost session — auto-close it
                $openSession->update(['session_end' => $lastActivity]);
            }
        }

        // Create new session
        $session = LearningSession::create([
            'user_id'          => $userId,
            'course_online_id' => $courseId,
            'content_id'       => $contentId,
            'content_type'     => $type,
            'session_start'    => now(),
        ]);

        // Upsert user_course_progress
        $existingCourseProgress = UserCourseProgress::where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->first();

        UserCourseProgress::updateOrCreate(
            ['user_id' => $userId, 'course_online_id' => $courseId],
            [
                'started_at'       => $existingCourseProgress?->started_at ?? now(),
                'last_accessed_at' => now(),
                'status'           => ($existingCourseProgress && $existingCourseProgress->status !== 'not_started')
                                          ? $existingCourseProgress->status
                                          : 'in_progress',
                'last_session_id'  => $session->id,
            ]
        );

        return [
            'session_id'      => $session->id,
            'resume_position' => (float) ($contentProgress?->playback_position ?? 0),
            'is_completed'    => false,
        ];
    }

    public function updateProgress(int $sessionId, int $userId, array $data): array
    {
        $session = LearningSession::find($sessionId);

        if (!$session) {
            abort(404, 'Session not found.');
        }

        if ($session->user_id !== $userId) {
            abort(403, 'Forbidden.');
        }

        // Silently ignore updates to closed sessions
        if ($session->session_end !== null) {
            return ['ok' => true];
        }

        // Update session counters — completion_percentage only ever increases
        $newVideoPct = max(
            (float) ($session->video_completion_percentage ?? 0),
            (float) $data['completion_percentage']
        );

        [$watchedSegments, $lastPosition, $unwatchedSkipped] = $this->applyPlayedRanges($session, $data);

        $session->update([
            'last_progress_at'            => now(),
            'active_playback_time'        => $data['active_playback_time'],
            'video_completion_percentage' => $newVideoPct,
            'skip_count'                  => $data['skip_count'] ?? $session->skip_count,
            'seek_count'                  => $data['seek_count'] ?? $session->seek_count,
            'replay_count'                => $data['replay_count'] ?? $session->replay_count,
            'pause_count'                 => $data['pause_count'] ?? $session->pause_count,
            'speed_changes'               => $data['speed_changes'] ?? $session->speed_changes,
            'watched_segments'            => $watchedSegments,
            'last_played_position'        => $lastPosition,
            'unwatched_seconds_skipped'   => $unwatchedSkipped,
        ]);

        // Upsert user_content_progress
        $existing = UserContentProgress::where('user_id', $userId)
            ->where('content_id', $session->content_id)
            ->first();

        $watchDelta   = max(0, $data['active_playback_time'] - ($existing?->watch_time ?? 0));
        $newCompletion = max((float) ($existing?->completion_percentage ?? 0), (float) $data['completion_percentage']);

        UserContentProgress::updateOrCreate(
            ['user_id' => $userId, 'content_id' => $session->content_id],
            [
                'course_online_id'      => $session->course_online_id,
                'module_id'             => ModuleContent::find($session->content_id)?->module_id,
                'content_type'          => $session->content_type,
                'playback_position'     => $data['playback_position'],
                'completion_percentage' => $newCompletion,
                'watch_time'            => ($existing?->watch_time ?? 0) + $watchDelta,
                'last_accessed_at'      => now(),
            ]
        );

        return ['ok' => true];
    }

    public function endSession(int $sessionId, int $userId, array $data): array
    {
        $session = LearningSession::find($sessionId);

        if (!$session) {
            abort(404, 'Session not found.');
        }

        if ($session->user_id !== $userId) {
            abort(403, 'Forbidden.');
        }

        // Idempotent — return existing result if already closed
        if ($session->session_end !== null) {
            $contentProgress = UserContentProgress::where('user_id', $userId)
                ->where('content_id', $session->content_id)
                ->first();

            $courseProgress = UserCourseProgress::where('user_id', $userId)
                ->where('course_online_id', $session->course_online_id)
                ->first();

            return [
                'session_id'               => $session->id,
                'attention_score'          => $session->attention_score,
                'is_suspicious'            => (bool) $session->is_suspicious,
                'content_completed'        => (bool) ($contentProgress?->is_completed ?? false),
                'course_progress_percentage' => (float) ($courseProgress?->progress_percentage ?? 0),
            ];
        }

        $content = ModuleContent::find($session->content_id);

        $wallClock  = max(0, (int) now()->diffInSeconds($session->session_start));
        [$watchedSegments, $lastPosition, $unwatchedSkipped] = $this->applyPlayedRanges($session, $data);
        $data['unwatched_seconds_skipped'] = $unwatchedSkipped;
        $attention  = $this->calculateAttentionScore($session, $content, $data);
        $suspicious = $this->isSuspicious($data, $content?->duration);

        $contentCompleted = false;

        DB::transaction(function () use (
            $session, $userId, $data, $wallClock, $attention, $suspicious, $content,
            $watchedSegments, $lastPosition, $unwatchedSkipped, &$contentCompleted
        ) {
            // Update session
            $session->update([
                'session_end'                 => now(),
                'wall_clock_seconds'          => $wallClock,
                'attention_score'             => $attention,
                'attention_score_config_id'   => $session->attention_score_config_id,
                'is_suspicious'               => $suspicious ? 1 : 0,
                'active_playback_time'        => $data['active_playback_time'],
                'watched_segments'            => $watchedSegments,
                'last_played_position'        => $lastPosition,
                'unwatched_seconds_skipped'   => $unwatchedSkipped,
                'video_completion_percentage' => max(
                    (float) ($session->video_completion_percentage ?? 0),
                    (float) $data['completion_percentage']
                ),
                'skip_count'                  => $data['skip_count'] ?? $session->skip_count,
                'seek_count'                  => $data['seek_count'] ?? $session->seek_count,
                'replay_count'                => $data['replay_count'] ?? $session->replay_count,
                'pause_count'                 => $data['pause_count'] ?? $session->pause_count,
                'speed_changes'               => $data['speed_changes'] ?? $session->speed_changes,
                'fullscreen_count'            => $data['fullscreen_count'] ?? $session->fullscreen_count,
                'events_log'                  => $data['events_log'] ?? $session->events_log,
            ]);

            // Upsert user_content_progress
            $existing = UserContentProgress::where('user_id', $userId)
                ->where('content_id', $session->content_id)
                ->first();

            $watchDelta = max(0, $data['active_playback_time'] - ($existing?->watch_time ?? 0));
            $wasCompleted = $existing?->is_completed ?? false;

            $finalCompletion = max((float) ($existing?->completion_percentage ?? 0), (float) $data['completion_percentage']);

            $updateData = [
                'course_online_id'      => $session->course_online_id,
                'module_id'             => $content?->module_id,
                'content_type'          => $session->content_type,
                'playback_position'     => $data['playback_position'],
                'completion_percentage' => $finalCompletion,
                'watch_time'            => ($existing?->watch_time ?? 0) + $watchDelta,
                'last_accessed_at'      => now(),
            ];

            if (!$wasCompleted && $finalCompletion >= 95) {
                $updateData['is_completed'] = true;
                $updateData['completed_at'] = now();
                $contentCompleted = true;
            }

            UserContentProgress::updateOrCreate(
                ['user_id' => $userId, 'content_id' => $session->content_id],
                $updateData
            );

            // Recalculate course progress
            app(ContentProgressService::class)->recalculateCourseProgress($userId, $session->course_online_id);

            // Write to reporting fact table
            $user = \App\Models\User::find($userId);
            DB::table('reporting_learning_sessions_fact')->insert([
                'session_id'            => $session->id,
                'user_id'               => $userId,
                'course_online_id'      => $session->course_online_id,
                'content_id'            => $session->content_id,
                'department_id'         => $user?->department_id,
                'session_date'          => $session->session_start->toDateString(),
                'active_playback_time'  => $data['active_playback_time'],
                'wall_clock_seconds'    => $wallClock,
                'completion_percentage' => $finalCompletion,
                'attention_score'       => $attention,
                'is_suspicious'         => $suspicious ? 1 : 0,
                'skip_count'            => $data['skip_count'] ?? $session->skip_count,
                'seek_count'            => $data['seek_count'] ?? $session->seek_count,
                'replay_count'          => $data['replay_count'] ?? $session->replay_count,
                'pause_count'           => $data['pause_count'] ?? $session->pause_count,
                'content_completed'     => $contentCompleted ? 1 : 0,
                'created_at'            => now(),
            ]);
        });

        $session->refresh();
        $courseProgress = UserCourseProgress::where('user_id', $userId)
            ->where('course_online_id', $session->course_online_id)
            ->first();

        return [
            'session_id'               => $session->id,
            'attention_score'          => $session->attention_score,
            'is_suspicious'            => (bool) $session->is_suspicious,
            'content_completed'        => $contentCompleted,
            'course_progress_percentage' => (float) ($courseProgress?->progress_percentage ?? 0),
        ];
    }

    /**
     * @param array{unwatched_seconds_skipped?: float} $data optionally carries a
     *   precomputed unwatched_seconds_skipped (set by endSession); otherwise
     *   falls back to the session's accumulated column.
     */
    public function calculateAttentionScore(
        LearningSession $session,
        ?ModuleContent $content,
        array $data = []
    ): int {
        $completionPct = (float) max(
            $session->video_completion_percentage,
            $data['completion_percentage'] ?? 0
        );

        if ($session->content_type === 'pdf') {
            return $this->attentionScoreEngine->calculatePdfScore($completionPct);
        }

        $config = $this->resolveConfig($session);
        $session->attention_score_config_id = $config->id;

        $metrics = [
            'active_playback_time'      => $data['active_playback_time'] ?? $session->active_playback_time,
            'video_duration'            => $content?->duration ?? 0,
            'completion_percentage'     => $completionPct,
            'speed_changes'             => $data['speed_changes'] ?? $session->speed_changes,
            'unwatched_seconds_skipped' => $data['unwatched_seconds_skipped'] ?? $session->unwatched_seconds_skipped ?? 0,
        ];

        $result = $this->attentionScoreEngine->calculateVideoScore($metrics, $config);

        return $result['score'];
    }

    /**
     * Uses the config the session was already tagged with (so an in-flight
     * session isn't rescored mid-way with a config that didn't exist when it
     * started), falling back to the currently active config for new sessions.
     */
    private function resolveConfig(LearningSession $session): AttentionScoreConfig
    {
        if ($session->attention_score_config_id) {
            $existing = AttentionScoreConfig::find($session->attention_score_config_id);
            if ($existing) {
                return $existing;
            }
        }

        return $this->attentionScoreConfigService->getActiveConfig();
    }

    /**
     * Merges any newly-reported played ranges into the session's
     * watched-segments map and accumulates unwatched_seconds_skipped for
     * gaps that jump over content never previously watched.
     *
     * @param array{played_ranges?: array<array{0:float,1:float}>, playback_position?: float} $data
     * @return array{0: array, 1: float, 2: float} [watchedSegments, lastPosition, unwatchedSecondsSkipped]
     */
    private function applyPlayedRanges(LearningSession $session, array $data): array
    {
        $segments  = $session->watched_segments ?? [];
        $lastPos   = (float) ($session->last_played_position ?? 0);
        $unwatched = (float) ($session->unwatched_seconds_skipped ?? 0);

        $ranges = $data['played_ranges'] ?? [];

        foreach ($ranges as $range) {
            $start = (float) $range[0];
            $end   = (float) $range[1];

            if ($end <= $start) {
                continue;
            }

            if ($start > $lastPos) {
                $unwatched += $this->attentionScoreEngine->computeUnwatchedSecondsSkipped($segments, $lastPos, $start);
            }

            $segments = $this->attentionScoreEngine->mergeWatchedSegment($segments, $start, $end);
            $lastPos  = max($lastPos, $end);
        }

        return [$segments, $lastPos, $unwatched];
    }

    public function isSuspicious(array $data, ?int $videoDuration): bool
    {
        $wallClock     = $data['wall_clock_time'] ?? 0;
        $completion    = (float) ($data['completion_percentage'] ?? 0);
        $activeTime    = $data['active_playback_time'] ?? 0;
        $skipCount     = $data['skip_count'] ?? 0;

        // Condition 1: very fast completion
        if ($wallClock < 120 && $completion > 50) {
            return true;
        }

        // Condition 2: excessive skipping
        if ($skipCount > 20) {
            return true;
        }

        // Condition 3: barely watched but high completion
        if ($videoDuration && $videoDuration > 0) {
            if (($activeTime / $videoDuration) < 0.15 && $completion > 80) {
                return true;
            }

            // Condition 4: active time way exceeds duration (bot-like)
            if ($activeTime > $videoDuration * 3) {
                return true;
            }
        }

        return false;
    }
}
