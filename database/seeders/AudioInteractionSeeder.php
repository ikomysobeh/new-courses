<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AudioInteractionSeeder extends Seeder
{
    public function run(): void
    {
        $admin  = DB::table('users')->where('role', 'admin')->first();
        $users  = DB::table('users')->where('role', 'user')->get();
        $audios = DB::table('audios')->whereNull('deleted_at')->get();

        if (! $admin || $users->isEmpty() || $audios->isEmpty()) {
            $this->command?->warn('AudioInteractionSeeder: missing admin, users, or audios — skipping.');
            return;
        }

        $assignments = 0;
        $progress    = 0;

        foreach ($users as $userIdx => $user) {
            // Each user gets 3–5 audio assignments, cycling deterministically through the list
            $count    = ($userIdx % 3 === 0) ? 5 : (($userIdx % 3 === 1) ? 4 : 3);
            $assigned = $audios->values()->filter(fn ($a, $i) => $i % 2 === $userIdx % 2)->take($count);

            if ($assigned->isEmpty()) {
                $assigned = $audios->take($count);
            }

            foreach ($assigned->values() as $j => $audio) {
                $exists = DB::table('audio_assignments')
                    ->where('audio_id', $audio->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $assignedAt = now()->subDays(rand(10, 30));

                DB::table('audio_assignments')->insert([
                    'audio_id'          => $audio->id,
                    'user_id'           => $user->id,
                    'assigned_by'       => $admin->id,
                    'assigned_at'       => $assignedAt,
                    'notification_sent' => 1,
                    'created_at'        => $assignedAt,
                    'updated_at'        => $assignedAt,
                ]);
                $assignments++;

                // Listening progress — spread across three states
                $completionPct = match ($j % 3) {
                    0       => 100.00,
                    1       => rand(40, 85),
                    default => rand(5, 35),
                };
                $isCompleted   = $completionPct >= 100.0;
                $duration      = max(1, (int) $audio->duration);
                $listenedSecs  = (int) ($duration * $completionPct / 100);

                DB::table('audio_progress')->updateOrInsert(
                    ['user_id' => $user->id, 'audio_id' => $audio->id],
                    [
                        'current_time'          => $isCompleted ? $duration : $listenedSecs,
                        'total_listened_time'   => $listenedSecs,
                        'is_completed'          => $isCompleted ? 1 : 0,
                        'completion_percentage' => $completionPct,
                        'last_accessed_at'      => now()->subDays(rand(0, 7)),
                        'created_at'            => $assignedAt,
                        'updated_at'            => now()->subDays(rand(0, 3)),
                    ]
                );
                $progress++;
            }
        }

        $this->command?->info("AudioInteractionSeeder: {$assignments} assignments, {$progress} progress records.");
    }
}
