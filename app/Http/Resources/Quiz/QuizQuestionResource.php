<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'quiz_id'                    => $this->quiz_id,
            'question_text'              => $this->question_text,
            'type'                       => $this->type,
            'points'                     => $this->points,
            'options'                    => $this->options,
            'correct_answer'             => $this->correct_answer,
            'correct_answer_explanation' => $this->correct_answer_explanation,
            'order'                      => $this->order,
            'created_at'                 => $this->created_at,
            'updated_at'                 => $this->updated_at,
        ];
    }
}
