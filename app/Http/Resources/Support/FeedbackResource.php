<?php

namespace App\Http\Resources\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use App\Http\Resources\BaseResource;

class FeedbackResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'title'          => $this->title,
            'description'    => $this->description,
            'status'         => $this->status,
            'admin_response' => $this->admin_response,
            'user'           => $this->relationLoaded('user')
                ? $this->formatUser()
                : new MissingValue(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * The user relation can resolve to null when the feedback row is orphaned
     * (deleted or soft-deleted author), so every access here is null-safe.
     */
    private function formatUser(): ?array
    {
        $user = $this->user;

        if (! $user) {
            return null;
        }

        $department = $user->relationLoaded('department') ? $user->department : null;

        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'department' => $department
                ? ['id' => $department->id, 'name' => $department->name]
                : null,
        ];
    }
}
