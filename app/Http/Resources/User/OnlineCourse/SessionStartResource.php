<?php

namespace App\Http\Resources\User\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionStartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        return [
            'session_id'      => $data['session_id'],
            'resume_position' => $data['resume_position'],
            'is_completed'    => $data['is_completed'],
        ];
    }
}
