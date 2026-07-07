<?php

namespace App\Services\Evaluation\Notification;

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
                $evalQuery = Evaluation::with(['user.department', 'user.userLevelTier', 'course', 'courseOnline', 'histories'])
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

                $detailedEvaluations = $this->buildDetailedEvaluations($evaluations);

                $reportMonth = !empty($filters['start_date'])
                    ? \Carbon\Carbon::parse($filters['start_date'])->format('F Y')
                    : null;

                Mail::to($manager->email)->queue(
                    new EvaluationReportMail(
                        manager:             ['name' => $manager->name],
                        detailedEvaluations: $detailedEvaluations,
                        emailSubject:        $subject,
                        customMessage:       $message ?: null,
                        reportMonth:         $reportMonth,
                    )
                );

                $sentTo[] = ['id' => $manager->id, 'email' => $manager->email];
            } catch (\Throwable $e) {
                $failedTo[] = ['id' => $manager->id, 'email' => $manager->email, 'error' => $e->getMessage()];
            }
        }

        $status = match (true) {
            count($sentTo) === 0   => 'failed',
            count($failedTo) === 0 => 'sent',
            default                => 'partial',
        };

        $notifSend = NotificationSend::create([
            'type'           => 'evaluation_report',
            'subject'        => $subject,
            'message'        => $message,
            'recipient_ids'  => array_column($sentTo, 'id'),
            'employee_ids'   => $users->pluck('id')->values()->toArray(),
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

    private function buildDetailedEvaluations(\Illuminate\Database\Eloquent\Collection $evaluations): array
    {
        $result = [];

        foreach ($evaluations->groupBy('user_id') as $userEvaluations) {
            $user = $userEvaluations->first()->user;

            $evaluationsList = [];
            $courseAverages  = [];
            $scoreSum        = 0;

            foreach ($userEvaluations as $eval) {
                $courseName = $eval->course_type === 'online'
                    ? optional($eval->courseOnline)->name
                    : optional($eval->course)->name;

                $detailedScores = $eval->histories->map(fn ($h) => [
                    'category_name' => $h->config_name,
                    'type_name'     => $h->type_name,
                    'score'         => $h->score_given,
                    'comments'      => '',
                ])->toArray();

                $maxScore      = $eval->performance_points_max;
                $courseAverage = ($maxScore > 0)
                    ? round(($eval->total_score / $maxScore) * 100, 1)
                    : 'N/A';

                $courseAverages[] = $courseAverage;
                $scoreSum        += $eval->total_score;

                $evaluationsList[] = [
                    'course'          => $courseName ?? 'N/A',
                    'total_score'     => $eval->total_score,
                    'created_at'      => $eval->created_at?->format('Y-m-d') ?? 'N/A',
                    'detailed_scores' => $detailedScores,
                ];
            }

            $totalEvaluations = $userEvaluations->count();
            $overallAverage   = $totalEvaluations > 0
                ? round($scoreSum / $totalEvaluations, 1)
                : 0;

            $result[] = [
                'employee' => [
                    'name'       => $user->name,
                    'department' => optional($user->department)->name ?? 'N/A',
                    'level'      => optional($user->userLevelTier)->tier_name ?? 'N/A',
                    'email'      => $user->email,
                ],
                'overall_average'   => $overallAverage,
                'total_evaluations' => $totalEvaluations,
                'course_averages'   => $courseAverages,
                'evaluations'       => $evaluationsList,
            ];
        }

        return $result;
    }

    public function getNotificationHistory(array $filters): LengthAwarePaginator
    {
        $paginator = NotificationSend::where('type', 'evaluation_report')
            ->latest('sent_at')
            ->paginate(20);

        // Legacy fallback: older rows have no employee_ids, derive from evaluations.
        $allEvaluationIds = collect($paginator->items())
            ->flatMap(fn ($n) => $n->evaluation_ids ?? [])
            ->unique()
            ->values()
            ->toArray();

        $evaluationUserMap = Evaluation::whereIn('id', $allEvaluationIds)
            ->pluck('user_id', 'id');

        // Build per-row employee id list (explicit employee_ids or legacy fallback).
        $rowEmployeeIds = [];
        foreach ($paginator->items() as $notifSend) {
            $ids = ! empty($notifSend->employee_ids)
                ? collect($notifSend->employee_ids)
                : collect($notifSend->evaluation_ids ?? [])
                    ->map(fn ($evalId) => $evaluationUserMap->get($evalId));

            $rowEmployeeIds[$notifSend->id] = $ids->filter()->unique()->values()->toArray();
        }

        // Load all involved employees (with report_to for manager resolution).
        $employeeIdsToLoad = collect($rowEmployeeIds)->flatten()->unique()->values()->toArray();
        $employees = User::whereIn('id', $employeeIdsToLoad)
            ->get(['id', 'name', 'email', 'report_to'])
            ->keyBy('id');

        // Resolve managers from employees' report_to so they appear even for
        // skipped-delivery rows (no evaluations matched, no manager email, etc.).
        $managerIds = $employees->pluck('report_to')->filter()->unique()->values()->toArray();
        $managers = User::whereIn('id', $managerIds)
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $toPayload = fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email];

        foreach ($paginator->items() as $notifSend) {
            $rowEmployees = collect($rowEmployeeIds[$notifSend->id] ?? [])
                ->map(fn ($id) => $employees->get($id))
                ->filter()
                ->unique('id')
                ->values();

            $notifSend->resolved_employees = $rowEmployees->map($toPayload)->toArray();

            $notifSend->resolved_managers = $rowEmployees
                ->pluck('report_to')
                ->filter()
                ->unique()
                ->map(fn ($mid) => $managers->get($mid))
                ->filter()
                ->map($toPayload)
                ->values()
                ->toArray();
        }

        return $paginator;
    }
}
