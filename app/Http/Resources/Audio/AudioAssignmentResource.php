<?php

namespace App\Http\Resources\Audio;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudioAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'audio' => $this->whenLoaded('audio', fn () => [
                'id' => $this->audio->id,
                'name' => $this->audio->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'manager' => $this->user->manager ? [
                    'id' => $this->user->manager->id,
                    'name' => $this->user->manager->name,
                    'email' => $this->user->manager->email,
                ] : null,
            ]),
            'assigned_by' => $this->whenLoaded('assignedBy', fn () => [
                'id' => $this->assignedBy->id,
                'name' => $this->assignedBy->name,
                'email' => $this->assignedBy->email,
            ]),
            'assigned_at' => $this->assigned_at,
            'notification_sent' => (bool) $this->notification_sent,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
