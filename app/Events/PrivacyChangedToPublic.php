<?php

namespace App\Events;

use App\Models\Course;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivacyChangedToPublic
{
    use Dispatchable, SerializesModels;

    /**
     * @param array<int> $excludedUserIds User IDs who were already assigned and should not be re-notified.
     */
    public function __construct(
        public readonly Course $course,
        public readonly array $excludedUserIds,
    ) {}
}
