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
    public function previewNotification(array $managerIds, array $filters): array
    {
        $managers = User::whereIn('id', $managerIds)->get();

        $employeeIds = User::whereIn('report_to', $managerIds)->pluck('id')->toArray();

        $evalQuery = Evaluation::whereIn('user_id', $employeeIds);
        if (!empty($filters['start_date'])) {
            $evalQuery->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $evalQuery->whereDate('created_at', '<=', $filters['end_date']);
        }

        $evaluationCount = $evalQuery->count();

        return [
            'managers'         => $managers->map(fn(User $m) => [
                'id'    => $m->id,
                'name'  => $m->name,
                'email' => $m->email,
            ])->values()->toArray(),
            'employee_count'   => count($employeeIds),
            'evaluation_count' => $evaluationCount,
            'date_range'       => [
                'start' => $filters['start_date'] ?? null,
                'end'   => $filters['end_date'] ?? null,
            ],
        ];
    }

    public function sendNotifications(
        array  $managerIds,
        array  $filters,
        string $subject,
        string $message,
        int    $sentBy
    ): array {
        $sentTo   = [];
        $failedTo = [];

        $managers = User::whereIn('id', $managerIds)->get();

        // Collect all evaluation IDs covered across all managers
        $allEvaluationIds = [];

        foreach ($managers as $manager) {
            try {
                $employeeIds = User::where('report_to', $manager->id)->pluck('id')->toArray();

                $evalQuery = Evaluation::with(['user', 'course', 'courseOnline'])
                    ->whereIn('user_id', $employeeIds);

                if (!empty($filters['start_date'])) {
                    $evalQuery->whereDate('created_at', '>=', $filters['start_date']);
                }
                if (!empty($filters['end_date'])) {
                    $evalQuery->whereDate('created_at', '<=', $filters['end_date']);
                }

                $evaluations = $evalQuery->get();

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
            count($failedTo) === 0                           => 'sent',
            count($sentTo) === 0                             => 'failed',
            default                                          => 'partial',
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
            'sent_to'       => $sentTo,
            'failed_to'     => $failedTo,
        ];
    }

    public function getNotificationHistory(array $filters): LengthAwarePaginator
    {
        return NotificationSend::where('type', 'evaluation_report')
            ->latest('sent_at')
            ->paginate(20);
    }
}
