<?php

namespace App\Listeners;

use App\Events\CourseAssigned;
use App\Mail\CourseAssignedManagerMail;
use App\Mail\CourseAssignedUserMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCourseAssignmentNotifications implements ShouldQueue
{
    public function handle(CourseAssigned $event): void
    {
        $this->sendUserEmail($event);
        $this->sendManagerEmail($event);
    }

    private function sendUserEmail(CourseAssigned $event): void
    {
        if (! $event->assignedUser->email) {
            return;
        }

        try {
            $loginLink = $event->assignedUser->generateCourseLoginLink((int) $event->course->id);

            Mail::to($event->assignedUser->email)->queue(
                new CourseAssignedUserMail($event->course, $event->assignedUser, $event->assignedBy, $loginLink)
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function sendManagerEmail(CourseAssigned $event): void
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
                new CourseAssignedManagerMail($event->course, $event->assignedUser, $manager, $event->assignedBy)
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
