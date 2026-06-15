<?php

namespace App\Jobs\Reporting;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RefreshKpiCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Flush reporting cache key families so next request re-builds from fresh data
        $patterns = [
            'reporting:kpi:',
            'reporting:user-course-daily:',
            'reporting:department-course-daily:',
            'reporting:session-fact:',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
