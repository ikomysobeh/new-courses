<?php

namespace App\Events;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Course $course,
        public readonly User $assignedUser,
        public readonly User $assignedBy,
    ) {}
}
