<?php

namespace App\Listeners;

use App\Events\UserEnrolledInPublicCourse;
use App\Mail\PublicCourseEnrollmentMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotifyManagerOfPublicEnrollment implements ShouldQueue
{
    public function handle(UserEnrolledInPublicCourse $event): void
    {
        if (! $event->user->report_to) {
            return;
        }

        $manager = User::query()->find($event->user->report_to);

        if (! $manager || ! $manager->email) {
            return;
        }

        try {
            Mail::to($manager->email)->queue(
                new PublicCourseEnrollmentMail($event->course, $event->user, $manager)
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
