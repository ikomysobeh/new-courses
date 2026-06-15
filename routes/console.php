<?php

use App\Jobs\CleanGhostSessionsJob;
use App\Jobs\GenerateDailyReportJob;
use App\Jobs\Reporting\RefreshDepartmentCourseDailyJob;
use App\Jobs\Reporting\RefreshKpiCacheJob;
use App\Jobs\Reporting\RefreshUserCourseDailyJob;
use App\Jobs\Reporting\SyncLearningSessionFactJob;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 6 scheduled jobs
Schedule::job(new CleanGhostSessionsJob)->hourly()->name('clean-ghost-sessions');
Schedule::job(new GenerateDailyReportJob)->dailyAt('02:00')->name('generate-daily-report');

// Phase 7 reporting jobs
// Sync session facts from last completed sessions every 30 minutes
Schedule::job(new SyncLearningSessionFactJob)->everyThirtyMinutes()->name('sync-session-fact');

// Rebuild daily aggregates for yesterday at 00:15
Schedule::call(function () {
    $yesterday = Carbon::yesterday()->toDateString();
    RefreshUserCourseDailyJob::dispatch($yesterday);
    RefreshDepartmentCourseDailyJob::dispatch($yesterday);
})->dailyAt('00:15')->name('refresh-daily-aggregates');

// Warm KPI cache at 01:00
Schedule::job(new RefreshKpiCacheJob)->dailyAt('01:00')->name('refresh-kpi-cache');
