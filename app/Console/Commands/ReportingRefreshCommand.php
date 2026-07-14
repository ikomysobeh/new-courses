<?php

namespace App\Console\Commands;

use App\Services\Reporting\ReportingRefreshService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReportingRefreshCommand extends Command
{
    protected $signature = 'reporting:refresh
                            {--date= : Refresh a single date (YYYY-MM-DD)}
                            {--from= : Start date for range refresh (YYYY-MM-DD)}
                            {--to=   : End date for range refresh (YYYY-MM-DD)}
                            {--full  : Full rebuild from earliest session}
                            {--progress : Rebuild the User Course Progress snapshot table}';

    protected $description = 'Refresh reporting tables from session and progress source data';

    public function handle(ReportingRefreshService $service): int
    {
        $date     = $this->option('date');
        $from     = $this->option('from');
        $to       = $this->option('to');
        $full     = $this->option('full');
        $progress = $this->option('progress');

        // Conflict checks
        if ($full && ($date || $from || $to)) {
            $this->error('--full cannot be combined with --date or --from/--to.');
            return Command::FAILURE;
        }

        if ($date && ($from || $to)) {
            $this->error('--date cannot be combined with --from/--to.');
            return Command::FAILURE;
        }

        if (($from && ! $to) || ($to && ! $from)) {
            $this->error('Both --from and --to are required for range refresh.');
            return Command::FAILURE;
        }

        try {
            if ($progress) {
                $this->info('Rebuilding User Course Progress snapshot...');
                $result = $service->refreshUserCourseProgress();
            } elseif ($full) {
                $this->info('Running full rebuild...');
                $result = $service->refreshFull();
                $this->info('Rebuilding User Course Progress snapshot...');
                $progressResult = $service->refreshUserCourseProgress();
                $result['rows_written'] += $progressResult['rows_written'];
            } elseif ($from && $to) {
                $this->info("Running date range refresh: {$from} to {$to}...");
                $result = $service->refreshDateRange(Carbon::parse($from), Carbon::parse($to));
            } else {
                $target = $date ? Carbon::parse($date) : Carbon::yesterday();
                $this->info("Running daily refresh for {$target->toDateString()}...");
                $result = $service->refreshDaily($target);
            }

            $this->info("Done. Status: {$result['status']} | Rows: {$result['rows_written']} | Duration: {$result['duration_sec']}s");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Refresh failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
