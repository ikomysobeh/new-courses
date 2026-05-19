<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ActivityService
{
    // ---- Action Key Constants ----

    const ACTION_FEEDBACK_SUBMITTED      = 'feedback.submitted';
    const ACTION_FEEDBACK_RESPONDED      = 'feedback.responded';
    const ACTION_FEEDBACK_STATUS_CHANGED = 'feedback.status_changed';

    const ACTION_BUG_REPORT_SUBMITTED = 'bug_report.submitted';
    const ACTION_BUG_REPORT_ASSIGNED  = 'bug_report.assigned';
    const ACTION_BUG_REPORT_RESOLVED  = 'bug_report.resolved';
    const ACTION_BUG_REPORT_CLOSED    = 'bug_report.closed';
    const ACTION_BUG_REPORT_UPDATED   = 'bug_report.updated';

    // ---- Writer ----

    /**
     * Write an activity log entry. Never throws — safe to call anywhere.
     */
    public static function log(
        string  $description,
        ?string $action     = null,
        ?User   $user       = null,
        ?Model  $model      = null,
        array   $properties = []
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'     => $user?->id,
            'description' => $description,
            'action'      => $action,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model?->id,
            'properties'  => empty($properties) ? null : $properties,
        ]);
    }

    // ---- Readers ----

    public static function getRecent(int $limit = 50): Collection
    {
        return ActivityLog::with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public static function getUserActivities(int $userId, int $limit = 20): Collection
    {
        return ActivityLog::with('user')
            ->forUser($userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public static function forModel(string $modelType, int $modelId): Collection
    {
        return ActivityLog::with('user')
            ->forModel($modelType, $modelId)
            ->latest()
            ->get();
    }
}
