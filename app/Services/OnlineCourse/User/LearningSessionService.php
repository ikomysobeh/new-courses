<?php

namespace App\Services\OnlineCourse\User;

use App\Models\CourseOnlineAssignment;
use App\Models\LearningSession;
use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\UserCourseProgress;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LearningSessionService
{
    public function startSession(int $userId, int $courseId, int $contentId, string $type): array
    {
        // Verify user is assigned to course
        $assigned = CourseOnlineAssignment::where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->whereNull('deleted_at')
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
        $session->update([
            'last_progress_at'            => now(),
            'active_playback_time'        => $data['active_playback_time'],
            'video_completion_percentage' => $newVideoPct,
            'skip_count'                  => $data['skip_count'] ?? $session->skip_count,
            'seek_count'                  => $data['seek_count'] ?? $session->seek_count,
            'replay_count'                => $data['replay_count'] ?? $session->replay_count,
            'pause_count'                 => $data['pause_count'] ?? $session->pause_count,
            'speed_changes'               => $data['speed_changes'] ?? $session->speed_changes,
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

        $wallClock  = (int) now()->diffInSeconds($session->session_start);
        $attention  = $this->calculateAttentionScore($session, $content, $data);
        $suspicious = $this->isSuspicious($data, $content?->duration);

        $contentCompleted = false;

        DB::transaction(function () use (
            $session, $userId, $data, $wallClock, $attention, $suspicious, $content, &$contentCompleted
        ) {
            // Update session
            $session->update([
                'session_end'                 => now(),
                'wall_clock_seconds'          => $wallClock,
                'attention_score'             => $attention,
                'is_suspicious'               => $suspicious ? 1 : 0,
                'active_playback_time'        => $data['active_playback_time'],
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
            return (int) min(100, $completionPct);
        }

        $duration = $content?->duration ?? 0;
        $base     = 50;

        // Time ratio scoring
        if ($duration > 0) {
            $ratio = ($data['active_playback_time'] ?? $session->active_playback_time) / $duration;

            if ($ratio >= 0.80 && $ratio <= 2.00) {
                $base += 25;
            } elseif ($ratio >= 0.50) {
                $base += 10;
            } elseif ($ratio < 0.30) {
                $base -= 25;
            } elseif ($ratio > 2.00) {
                $base -= 15;
            }
        }

        // Completion bonus
        if ($completionPct >= 90) {
            $base += 20;
        } elseif ($completionPct >= 70) {
            $base += 10;
        } elseif ($completionPct < 20) {
            $base -= 20;
        }

        // Engagement adjustments
        $replayCount = $data['replay_count'] ?? $session->replay_count;
        $skipCount   = $data['skip_count'] ?? $session->skip_count;
        $speedChange = $data['speed_changes'] ?? $session->speed_changes;

        if ($replayCount >= 3) {
            $base += 5;
        }
        if ($skipCount > 15) {
            $base -= 15;
        } elseif ($skipCount > 8) {
            $base -= 8;
        }
        if ($speedChange > 3) {
            $base -= 5;
        }

        return max(0, min(100, $base));
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
