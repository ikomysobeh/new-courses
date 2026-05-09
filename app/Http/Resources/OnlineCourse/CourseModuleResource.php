<?php

namespace App\Http\Resources\OnlineCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'course_online_id'   => $this->course_online_id,
            'name'               => $this->name,
            'description'        => $this->description,
            'order_number'       => $this->order_number,
            'estimated_duration' => $this->estimated_duration,
            'has_quiz'           => $this->has_quiz,
            'quiz_required'      => $this->quiz_required,
            'contents'           => ModuleContentResource::collection($this->whenLoaded('contents')),
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
