<?php

namespace App\Listeners;

use App\Events\AudioAssigned;
use App\Mail\AudioAssignedManagerMail;
use App\Mail\AudioAssignedUserMail;
use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAudioAssignmentNotifications
{
    public function handle(AudioAssigned $event): void
    {
        
        $audio = Audio::query()->find($event->audioId);
        $assignedBy = User::query()->find($event->assignedById);

        if (! $audio || ! $assignedBy) {
            return;
        }

        $assignments = AudioAssignment::query()
            ->with(['user.manager', 'user.department'])
            ->whereIn('id', $event->assignmentIds)
            ->get();
        $userSummary = $this->sendUserEmails($assignments, $audio, $assignedBy);
        $managerSummary = $this->sendGroupedManagerEmails($assignments, $audio, $assignedBy);


    }

    private function sendUserEmails(Collection $assignments, Audio $audio, User $assignedBy): array
    {
        $queued = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($assignments as $assignment) {
            try {
                $user = $assignment->user;

                if (! $user || ! $user->email) {
                    $skipped++;

                   

                    continue;
                }

                $loginLink = $user->generateAudioLoginLink((int) $audio->id);

                Mail::to($user->email)->queue(new AudioAssignedUserMail(
                    user: $user,
                    assignedBy: $assignedBy,
                    audio: $audio,
                    loginLink: $loginLink,
                ));

                $assignment->update(['notification_sent' => true]);
                $queued++;

                
            } catch (Throwable $exception) {
                $failed++;

                

                report($exception);
            }
        }

        return [
            'queued' => $queued,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    private function sendGroupedManagerEmails(Collection $assignments, Audio $audio, User $assignedBy): array
    {
        $byManager = collect();
        $queued = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($assignments as $assignment) {
            $user = $assignment->user;
            $manager = $user?->manager;

            if (! $manager || ! $manager->email || $manager->id === $user?->id) {
                $skipped++;
                continue;
            }

            if (! $byManager->has($manager->id)) {
                $byManager->put($manager->id, [
                    'manager' => $manager,
                    'employees' => collect(),
                ]);
            }

            $entry = $byManager->get($manager->id);
            $entry['employees']->push($user);
            $byManager->put($manager->id, $entry);
        }

        foreach ($byManager as $group) {
            try {
                $manager = $group['manager'];
                $employees = $group['employees']->unique('id')->values();

                if ($employees->isEmpty()) {
                    $skipped++;
                    continue;
                }

                Mail::to($manager->email)->queue(new AudioAssignedManagerMail(
                    manager: $manager,
                    teamMembers: $employees,
                    assignedBy: $assignedBy,
                    audio: $audio,
                ));

                $queued++;

            
            } catch (Throwable $exception) {
                $failed++;

                

                report($exception);
            }
        }

        return [
            'queued' => $queued,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }
}
