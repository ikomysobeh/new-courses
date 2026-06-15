<?php

namespace App\Jobs\Reporting;

use App\Services\Reporting\Aggregation\LearningSessionFactAggregatorService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncLearningSessionFactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected ?string $date = null) {}

    public function handle(LearningSessionFactAggregatorService $aggregator): void
    {
        $date = $this->date ? Carbon::parse($this->date) : Carbon::yesterday();
        $aggregator->backfillByDate($date);
    }
}
