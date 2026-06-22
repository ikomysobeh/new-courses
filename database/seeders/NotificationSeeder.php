<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $admin   = DB::table('users')->where('role', 'admin')->first();
        $users   = DB::table('users')->where('role', 'user')->get();

        if (! $admin || $users->isEmpty()) {
            $this->command?->warn('NotificationSeeder: missing admin or users — skipping.');
            return;
        }

        $evaluationIds = DB::table('evaluations')->pluck('id')->toArray();
        $allUserIds    = $users->pluck('id')->toArray();
        $notifRows     = 0;

        $sends = [
            [
                'type'           => 'evaluation_reminder',
                'subject'        => 'Evaluation Submitted Successfully',
                'message'        => 'Your performance evaluation has been recorded. Please review your results in the training portal.',
                'recipient_count'=> min(count($allUserIds), 10),
                'eval_count'     => min(count($evaluationIds), 5),
                'days_ago'       => 3,
                'status'         => 'sent',
            ],
            [
                'type'           => 'course_assignment',
                'subject'        => 'New Training Course Assigned',
                'message'        => 'You have been assigned to a new training course. Please complete it before the deadline shown in the portal.',
                'recipient_count'=> count($allUserIds),
                'eval_count'     => 0,
                'days_ago'       => 8,
                'status'         => 'sent',
            ],
            [
                'type'           => 'quiz_reminder',
                'subject'        => 'Upcoming Quiz — Action Required',
                'message'        => 'A mandatory quiz is scheduled for this week. Please log in and complete it on time.',
                'recipient_count'=> min(count($allUserIds), 8),
                'eval_count'     => 0,
                'days_ago'       => 14,
                'status'         => 'sent',
            ],
            [
                'type'           => 'evaluation_reminder',
                'subject'        => 'Monthly Performance Review Available',
                'message'        => 'Your monthly performance evaluation summary is now available. Log in to view your detailed scores and feedback.',
                'recipient_count'=> count($allUserIds),
                'eval_count'     => min(count($evaluationIds), 10),
                'days_ago'       => 21,
                'status'         => 'sent',
            ],
        ];

        foreach ($sends as $send) {
            $recipients = array_slice($allUserIds, 0, $send['recipient_count']);
            $evalIds    = $send['eval_count'] > 0
                ? array_slice($evaluationIds, 0, $send['eval_count'])
                : [];
            $sentAt = now()->subDays($send['days_ago']);

            $sendId = DB::table('notification_sends')->insertGetId([
                'type'           => $send['type'],
                'subject'        => $send['subject'],
                'message'        => $send['message'],
                'recipient_ids'  => json_encode($recipients),
                'evaluation_ids' => json_encode($evalIds),
                'status'         => $send['status'],
                'sent_by'        => $admin->id,
                'sent_at'        => $sentAt,
                'created_at'     => $sentAt,
                'updated_at'     => $sentAt,
            ]);

            foreach ($recipients as $userId) {
                $isRead = (($userId + $send['days_ago']) % 3 !== 0); // deterministic mix
                DB::table('user_notifications')->insert([
                    'user_id'              => $userId,
                    'notification_send_id' => $sendId,
                    'type'                 => $send['type'],
                    'title'                => $send['subject'],
                    'body'                 => $send['message'],
                    'read_at'              => $isRead ? now()->subDays(max(0, $send['days_ago'] - 2)) : null,
                    'created_at'           => $sentAt,
                    'updated_at'           => $sentAt,
                ]);
                $notifRows++;
            }
        }

        $this->command?->info('NotificationSeeder: ' . count($sends) . " sends, {$notifRows} user notifications.");
    }
}
