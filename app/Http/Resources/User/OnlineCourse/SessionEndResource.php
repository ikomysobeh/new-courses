<?php

namespace App\Http\Resources\User\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionEndResource extends JsonResource
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
