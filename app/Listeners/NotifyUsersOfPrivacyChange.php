<?php

namespace App\Listeners;

use App\Events\PrivacyChangedToPublic;
use App\Mail\CoursePrivacyChangeMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotifyUsersOfPrivacyChange implements ShouldQueue
{
    public function handle(PrivacyChangedToPublic $event): void
    {
        $users = User::query()
            ->where('role', '!=', 'admin')
            ->whereNotNull('email')
            ->whereNotIn('id', $event->excludedUserIds)
            ->get();

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->queue(new CoursePrivacyChangeMail($event->course, $user));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
