<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserQuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'description'          => $this->description,
            'pass_threshold'       => $this->pass_threshold,
            'total_points'         => $this->total_points,
            'time_limit_minutes'   => $this->time_limit_minutes,
            'deadline'             => $this->deadline,
            'show_correct_answers' => $this->show_correct_answers,
            'questions'            => $this->whenLoaded(
                'questions',
                fn () => UserQuizQuestionResource::collection($this->questions)
            ),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
