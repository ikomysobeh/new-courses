<?php

namespace App\Listeners;

use App\Events\PublicCourseCreated;
use App\Mail\CoursePublicAnnouncementMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotifyAllUsersOfPublicCourse implements ShouldQueue
{
    public function handle(PublicCourseCreated $event): void
    {
        $users = User::query()
            ->where('role', '!=', 'admin')
            ->whereNotNull('email')
            ->get();

        foreach ($users as $user) {
            try {
                $loginLink = $user->generateCourseLoginLink((int) $event->course->id);
                Mail::to($user->email)->queue(new CoursePublicAnnouncementMail($event->course, $user, $loginLink));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
