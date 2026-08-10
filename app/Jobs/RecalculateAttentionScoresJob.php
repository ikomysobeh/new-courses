<?php

namespace App\Jobs;

use App\Models\AttentionScoreRecalculationJob;
use App\Services\AttentionScore\AttentionScoreRecalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateAttentionScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(private readonly int $jobId) {}

    public function handle(AttentionScoreRecalculationService $service): void
    {
        $job = AttentionScoreRecalculationJob::findOrFail($this->jobId);

        try {
            $service->markRunning($job);

            do {
                $processed = $service->recalculateChunk($job);
                $job->refresh();
            } while ($processed > 0);

            $service->markDone($job);
        } catch (\Throwable $e) {
            Log::error('RecalculateAttentionScoresJob failed', [
                'job_id' => $this->jobId,
                'error'  => $e->getMessage(),
            ]);

            $service->markFailed($job, $e->getMessage());

            throw $e;
        }
    }
}
