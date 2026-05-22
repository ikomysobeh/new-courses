<?php

namespace App\Services\Evaluation\Notification;

use App\Enums\PerformanceLevel;
use App\Mail\EvaluationReportMail;
use App\Models\Evaluation;
use App\Models\NotificationSend;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class EvaluationNotificationService
{
    public function previewNotification(array $userIds, array $filters): array
    {
        $users = User::whereIn('id', $userIds)->get();

        // Resolve unique managers from report_to
        $managerIds = $users->pluck('report_to')->filter()->unique()->values()->toArray();
        $managers   = User::whereIn('id', $managerIds)->get();

        $evalQuery = Evaluation::whereIn('user_id', $userIds);
        if (!empty($filters['start_date'])) {
            $evalQuery->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $evalQuery->whereDate('created_at', '<=', $filters['end_date']);
        }

        $evaluationCount = $evalQuery->count();

        return [
            'managers'         => $managers->map(fn (User $m) => [
                'id'    => $m->id,
                'name'  => $m->name,
                'email' => $m->email,
            ])->values()->toArray(),
            'employee_count'   => count($userIds),
            'evaluation_count' => $evaluationCount,
            'date_range'       => [
                'start' => $filters['start_date'] ?? null,
                'end'   => $filters['end_date'] ?? null,
            ],
        ];
    }

    public function sendNotifications(
        array  $userIds,
        array  $filters,
        string $subject,
        string $message,
        int    $sentBy
    ): array {
        $sentTo    = [];
        $failedTo  = [];
        $skippedTo = [];

        // Group user IDs by their manager (report_to)
        $users = User::whereIn('id', $userIds)->get();

        $byManager = [];
        foreach ($users as $user) {
            if (!$user->report_to) {
                $skippedTo[] = ['id' => $user->id, 'reason' => 'no_manager'];
                continue;
            }
            $byManager[$user->report_to][] = $user->id;
        }

        $allEvaluationIds = [];

        foreach ($byManager as $managerId => $employeeIds) {
            $manager = User::find($managerId);

            if (!$manager || !$manager->email) {
                foreach ($employeeIds as $uid) {
                    $skippedTo[] = ['id' => $uid, 'reason' => 'manager_has_no_email'];
                }
                continue;
            }

            try {
                $evalQuery = Evaluation::with(['user', 'course', 'courseOnline'])
                    ->whereIn('user_id', $employeeIds);

                if (!empty($filters['start_date'])) {
                    $evalQuery->whereDate('created_at', '>=', $filters['start_date']);
                }
                if (!empty($filters['end_date'])) {
                    $evalQuery->whereDate('created_at', '<=', $filters['end_date']);
                }

                $evaluations = $evalQuery->get();

                if ($evaluations->isEmpty()) {
                    foreach ($employeeIds as $uid) {
                        $skippedTo[] = ['id' => $uid, 'reason' => 'no_evaluations'];
                    }
                    continue;
                }

                foreach ($evaluations->pluck('id')->toArray() as $id) {
                    $allEvaluationIds[] = $id;
                }

                Mail::to($manager->email)->queue(
                    new EvaluationReportMail(
                        manager:     $manager,
                        evaluations: $evaluations,
                        mailSubject: $subject,
                        mailMessage: $message,
                        startDate:   $filters['start_date'] ?? null,
                        endDate:     $filters['end_date'] ?? null,
                    )
                );

                $sentTo[] = ['id' => $manager->id, 'email' => $manager->email];
            } catch (\Throwable $e) {
                $failedTo[] = ['id' => $manager->id, 'email' => $manager->email, 'error' => $e->getMessage()];
            }
        }

        // Determine overall status
        $status = match (true) {
            count($failedTo) === 0 => 'sent',
            count($sentTo) === 0   => 'failed',
            default                => 'partial',
        };

        // Write one notification_sends row
        $notifSend = NotificationSend::create([
            'type'           => 'evaluation_report',
            'subject'        => $subject,
            'message'        => $message,
            'recipient_ids'  => array_column($sentTo, 'id'),
            'evaluation_ids' => array_unique($allEvaluationIds),
            'status'         => $status,
            'sent_by'        => $sentBy,
            'sent_at'        => now(),
        ]);

        // Write one user_notifications row per successfully dispatched manager
        foreach ($sentTo as $recipient) {
            UserNotification::create([
                'user_id'              => $recipient['id'],
                'notification_send_id' => $notifSend->id,
                'type'                 => 'evaluation_report',
                'title'                => $subject,
                'body'                 => $message,
                'read_at'              => null,
            ]);
        }

        return [
            'success_count' => count($sentTo),
            'failed_count'  => count($failedTo),
            'skipped_count' => count($skippedTo),
            'sent_to'       => $sentTo,
            'failed_to'     => $failedTo,
            'skipped'       => $skippedTo,
        ];
    }

    public function getNotificationHistory(array $filters): LengthAwarePaginator
    {
        return NotificationSend::where('type', 'evaluation_report')
            ->latest('sent_at')
            ->paginate(20);
    }
}
