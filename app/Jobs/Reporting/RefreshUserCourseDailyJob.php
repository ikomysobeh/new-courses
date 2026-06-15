<?php

namespace App\Jobs\Reporting;

use App\Services\Reporting\Aggregation\UserCourseDailyAggregatorService;
use App\Services\Reporting\ReportingRefreshService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshUserCourseDailyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $date) {}

    public function handle(UserCourseDailyAggregatorService $aggregator): void
    {
        $aggregator->aggregateForDate(Carbon::parse($this->date));
    }
}
