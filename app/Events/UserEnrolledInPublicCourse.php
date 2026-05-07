<?php

namespace App\Events;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserEnrolledInPublicCourse
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Course $course,
        public readonly User $user,
    ) {}
}
