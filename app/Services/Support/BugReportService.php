<?php

namespace App\Services\Support;

use App\Models\BugReport;
use App\Models\User;
use App\Services\ActivityService;
use App\Support\Filtering\FilterableQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BugReportService
{
    use FilterableQuery;

    public function createReport(int $adminUserId, array $data): BugReport
    {
        $report = BugReport::create([
            'reported_by'        => $adminUserId,
            'assigned_to'        => $data['assigned_to'] ?? null,
            'title'              => $data['title'],
            'description'        => $data['description'],
            'priority'           => $data['priority'],
            'steps_to_reproduce' => $data['steps_to_reproduce'] ?? null,
            'page_url'           => $data['page_url'] ?? null,
            'status'             => 'open',
        ]);

        $user = User::find($adminUserId);

        ActivityService::log(
            "Bug report created: {$report->title}",
            ActivityService::ACTION_BUG_REPORT_SUBMITTED,
            $user,
            $report
        );

        return $report->load(['reporter', 'assignee']);
    }

    public function getAllForAdmin(array $params = []): LengthAwarePaginator
    {
        $query = BugReport::with(['reporter', 'assignee']);

        return $this->applyFilters($query, $params, [
            'searchable'  => ['title', 'description'],
            'filters'     => [
                'status'      => 'exact',
                'priority'    => 'exact',
                'assigned_to' => 'exact',
            ],
            'dateColumn'  => 'created_at',
            'sortable'    => ['created_at', 'status', 'priority'],
            'defaultSort' => ['created_at', 'desc'],
            'perPage'     => 15,
        ]);
    }

    public function getById(int $id): BugReport
    {
        return BugReport::with(['reporter', 'assignee'])->findOrFail($id);
    }

    public function update(int $id, array $data): BugReport
    {
        $report = BugReport::findOrFail($id);

        $report->update(array_filter([
            'priority'    => $data['priority'] ?? null,
            'status'      => $data['status'] ?? null,
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : null,
        ], fn($v) => $v !== null));

        ActivityService::log(
            "Bug report updated: {$report->title}",
            ActivityService::ACTION_BUG_REPORT_UPDATED,
            null,
            $report,
            array_filter($data)
        );

        return $report->load(['reporter', 'assignee']);
    }

    public function assign(int $id, int $adminUserId): BugReport
    {
        $report = BugReport::findOrFail($id);

        $report->update(['assigned_to' => $adminUserId]);

        $assignee = User::find($adminUserId);

        ActivityService::log(
            "Bug report assigned: {$report->title}",
            ActivityService::ACTION_BUG_REPORT_ASSIGNED,
            null,
            $report,
            ['assigned_to' => $adminUserId, 'assignee_name' => $assignee?->name]
        );

        return $report->load(['reporter', 'assignee']);
    }

    public function resolve(int $id): BugReport
    {
        $report = BugReport::findOrFail($id);

        $report->markResolved();

        ActivityService::log(
            "Bug report resolved: {$report->title}",
            ActivityService::ACTION_BUG_REPORT_RESOLVED,
            null,
            $report
        );

        return $report->load(['reporter', 'assignee']);
    }

    public function delete(int $id): void
    {
        BugReport::findOrFail($id)->delete();
    }
}
