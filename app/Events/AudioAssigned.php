<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AudioAssigned
{
    use Dispatchable, SerializesModels;

    /**
     * @param array<int> $assignmentIds
     */
    public function __construct(
        public readonly int $audioId,
        public readonly array $assignmentIds,
        public readonly int $assignedById,
    ) {}
}
