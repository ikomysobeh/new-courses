<?php

namespace App\Listeners;

use App\Events\OnlineCourseAssigned;
use App\Mail\OnlineCourseAssignedManagerMail;
use App\Mail\OnlineCourseAssignedUserMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOnlineCourseAssignmentNotifications implements ShouldQueue
{
    public function handle(OnlineCourseAssigned $event): void
    {
        $this->sendUserEmail($event);
        $this->sendManagerEmail($event);
    }

    private function sendUserEmail(OnlineCourseAssigned $event): void
    {
        if (! $event->assignedUser->email) {
            return;
        }

        try {
            $loginLink = $event->assignedUser->generateOnlineCourseLoginLink((int) $event->course->id);

            Mail::to($event->assignedUser->email)->queue(
                new OnlineCourseAssignedUserMail(
                    course:       $event->course,
                    assignedUser: $event->assignedUser,
                    assignedBy:   $event->assignedBy,
                    loginLink:    $loginLink,
                )
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function sendManagerEmail(OnlineCourseAssigned $event): void
    {
        if (! $event->assignedUser->report_to) {
            return;
        }

        $manager = User::query()->find($event->assignedUser->report_to);

        if (! $manager || ! $manager->email) {
            return;
        }

        try {
            Mail::to($manager->email)->queue(
                new OnlineCourseAssignedManagerMail(
                    course:       $event->course,
                    assignedUser: $event->assignedUser,
                    manager:      $manager,
                    assignedBy:   $event->assignedBy,
                )
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
