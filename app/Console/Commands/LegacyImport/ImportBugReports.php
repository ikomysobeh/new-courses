<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\BugReport;
use App\Models\User;

class ImportBugReports extends LegacyImportCommand
{
    protected $signature = 'legacy:import-bug-reports';

    protected $description = 'Import bug_reports - identical schema, remaps reported_by/assigned_to.';

    protected array $userMap = [];

    protected function legacyTable(): string
    {
        return 'bug_reports';
    }

    protected function newModel(): string
    {
        return BugReport::class;
    }

    protected function beforeImport(): void
    {
        $this->userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newReportedBy = $this->userMap[$old['reported_by']] ?? null;

        if ($newReportedBy === null) {
            $this->error("No imported User for legacy reported_by={$old['reported_by']} (bug_report legacy_id={$old['id']})");

            return null;
        }

        $newAssignedTo = null;

        if ($old['assigned_to'] !== null) {
            $newAssignedTo = $this->userMap[$old['assigned_to']] ?? null;

            if ($newAssignedTo === null) {
                $this->error("No imported User for legacy assigned_to={$old['assigned_to']} (bug_report legacy_id={$old['id']})");

                return null;
            }
        }

        return [
            'legacy_id' => $old['id'],
            'reported_by' => $newReportedBy,
            'assigned_to' => $newAssignedTo,
            'priority' => $old['priority'],
            'status' => $old['status'],
            'title' => $old['title'],
            'description' => $old['description'],
            'steps_to_reproduce' => $old['steps_to_reproduce'],
            'page_url' => $old['page_url'],
            'resolved_at' => $old['resolved_at'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }
}
