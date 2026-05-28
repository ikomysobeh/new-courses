<?php

namespace App\Events;

use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnlineCourseAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CourseOnline $course,
        public readonly User $assignedUser,
        public readonly User $assignedBy,
    ) {}
}
