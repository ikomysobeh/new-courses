<?php

namespace App\Http\Resources\User\OnlineCourse;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class SessionStartResource extends BaseResource
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
