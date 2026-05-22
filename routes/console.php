<?php

use App\Jobs\CleanGhostSessionsJob;
use App\Jobs\GenerateDailyReportJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 6 scheduled jobs
Schedule::job(new CleanGhostSessionsJob)->hourly()->name('clean-ghost-sessions');
Schedule::job(new GenerateDailyReportJob)->dailyAt('02:00')->name('generate-daily-report');
