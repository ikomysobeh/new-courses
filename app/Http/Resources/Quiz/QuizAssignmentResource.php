<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'quiz_id'           => $this->quiz_id,
            'user'              => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),
            'assigned_by'       => $this->whenLoaded('assigner', fn () => [
                'id'    => $this->assigner->id,
                'name'  => $this->assigner->name,
                'email' => $this->assigner->email,
            ]),
            'assigned_at'       => $this->assigned_at,
            'notification_sent' => (bool) $this->notification_sent,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
