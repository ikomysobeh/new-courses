<?php

namespace App\Http\Resources\User\OnlineCourse;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class SessionEndResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        return [
            'session_id'               => $data['session_id'],
            'attention_score'          => $data['attention_score'],
            'content_completed'        => $data['content_completed'],
            'course_progress_percentage' => $data['course_progress_percentage'],
        ];
    }
}
