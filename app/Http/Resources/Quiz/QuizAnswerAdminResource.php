<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class QuizAnswerAdminResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'quiz_question_id' => $this->quiz_question_id,
            'answer'           => $this->answer,
            'is_correct'       => $this->is_correct,
            'points_earned'    => $this->points_earned,
            'question'         => $this->whenLoaded('question', fn () => [
                'id'            => $this->question->id,
                'question_text' => $this->question->question_text,
                'type'          => $this->question->type,
                'points'        => $this->question->points,
            ]),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
