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
        $managers = $event->user->managers()->get();

        foreach ($managers as $manager) {
            if (! $manager->email) {
                continue;
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
}
