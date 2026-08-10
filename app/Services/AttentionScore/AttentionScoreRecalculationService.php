<?php

namespace App\Services\AttentionScore;

use App\Jobs\RecalculateAttentionScoresJob;
use App\Models\AttentionScoreConfig;
use App\Models\AttentionScoreRecalculationJob;
use App\Models\LearningSession;
use App\Models\ModuleContent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AttentionScoreRecalculationService
{
    public function __construct(private readonly AttentionScoreEngine $engine) {}

    public function dispatchRecalculation(AttentionScoreConfig $config): AttentionScoreRecalculationJob
    {
        $total = LearningSession::whereIn('content_type', ['video', 'pdf'])->count();

        $job = AttentionScoreRecalculationJob::create([
            'attention_score_config_id' => $config->id,
            'status'                    => 'queued',
            'total_sessions'            => $total,
        ]);

        RecalculateAttentionScoresJob::dispatch($job->id);

        return $job;
    }

    /**
     * Recalculates attention_score for one chunk of sessions still on an
     * older config, using the job's target config. Idempotent: sessions
     * already tagged with the target config are skipped, so re-running
     * (e.g. after a queue worker restart) is safe.
     *
     * @return int number of sessions actually recalculated in this chunk
     */
    public function recalculateChunk(AttentionScoreRecalculationJob $job, int $chunkSize = 500): int
    {
        $config = $job->config;

        $sessions = LearningSession::where(function ($query) use ($config) {
                $query->where('attention_score_config_id', '!=', $config->id)
                    ->orWhereNull('attention_score_config_id');
            })
            ->orderBy('id')
            ->limit($chunkSize)
            ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }

        $contentIds = $sessions->pluck('content_id')->filter()->unique();
        $contents   = ModuleContent::whereIn('id', $contentIds)->get()->keyBy('id');

        $processed = 0;

        foreach ($sessions as $session) {
            $content = $contents->get($session->content_id);

            $score = $session->content_type === 'pdf'
                ? $this->engine->calculatePdfScore((float) $session->video_completion_percentage)
                : $this->engine->calculateVideoScore([
                    'active_playback_time'      => (float) $session->active_playback_time,
                    'video_duration'            => (float) ($content?->duration ?? 0),
                    'completion_percentage'     => (float) $session->video_completion_percentage,
                    'speed_changes'             => (int) $session->speed_changes,
                    'unwatched_seconds_skipped' => (float) ($session->unwatched_seconds_skipped ?? 0),
                ], $config)['score'];

            $session->update([
                'attention_score'           => $score,
                'attention_score_config_id' => $config->id,
            ]);

            DB::table('reporting_learning_sessions_fact')
                ->where('session_id', $session->id)
                ->update([
                    'attention_score'           => $score,
                    'attention_score_config_id' => $config->id,
                ]);

            $processed++;
        }

        $job->increment('processed_sessions', $processed);

        return $processed;
    }

    public function markRunning(AttentionScoreRecalculationJob $job): void
    {
        $job->update(['status' => 'running', 'started_at' => $job->started_at ?? now()]);
    }

    public function markDone(AttentionScoreRecalculationJob $job): void
    {
        $job->update(['status' => 'done', 'finished_at' => now()]);
    }

    public function markFailed(AttentionScoreRecalculationJob $job, string $message): void
    {
        $job->update(['status' => 'failed', 'error_message' => $message, 'finished_at' => now()]);
    }
}
