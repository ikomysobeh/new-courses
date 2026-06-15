<?php

namespace App\Http\Resources\Reporting;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserCourseProgressResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $progress = round((float) $this->progress_percentage, 2);
        $isDone   = $this->status === 'completed';

        $daysOverdue      = $this->daysOverdue($isDone);
        $complianceStatus = $this->complianceStatus($isDone, $daysOverdue);

        return [
            'id'                      => $this->id,
            'user_id'                 => $this->user_id,
            'user_name'               => $this->user_name,
            'user_email'              => $this->user_email,
            'department_id'           => $this->department_id,
            'department_name'         => $this->department_name,
            'course_online_id'        => $this->course_online_id,
            'course_name'             => $this->course_name,
            'course_deadline'         => $this->course_deadline,
            'progress_percentage'     => $progress,
            'status'                  => $this->status,
            'total_content_items'     => (int) $this->total_content_items,
            'completed_content_items' => (int) $this->completed_content_items,
            'started_at'              => $this->started_at,
            'completed_at'            => $this->completed_at,
            'last_accessed_at'        => $this->last_accessed_at,
            'days_overdue'            => $daysOverdue,
            'compliance_status'       => $complianceStatus,
            'score_band'              => $this->scoreBand($progress),
        ];
    }

    private function daysOverdue(bool $isDone): int
    {
        if ($isDone || ! $this->course_deadline) {
            return 0;
        }

        $deadline = Carbon::parse($this->course_deadline);

        return $deadline->isPast() ? (int) $deadline->diffInDays(Carbon::now()) : 0;
    }

    private function complianceStatus(bool $isDone, int $daysOverdue): string
    {
        if ($isDone) {
            return 'compliant';
        }
        if (! $this->course_deadline) {
            return 'on_track';
        }
        if ($daysOverdue > 0) {
            return 'non_compliant';
        }

        // deadline in the future — at risk if within the next 7 days
        return Carbon::parse($this->course_deadline)->lessThanOrEqualTo(Carbon::now()->addDays(7))
            ? 'at_risk'
            : 'on_track';
    }

    private function scoreBand(float $progress): string
    {
        return match (true) {
            $progress >= 85 => 'excellent',
            $progress >= 70 => 'good',
            $progress >= 50 => 'average',
            default         => 'poor',
        };
    }
}
