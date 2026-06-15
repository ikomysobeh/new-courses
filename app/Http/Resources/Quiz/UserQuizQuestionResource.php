<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class UserQuizQuestionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'quiz_id'       => $this->quiz_id,
            'question_text' => $this->question_text,
            'type'          => $this->type,
            'points'        => $this->points,
            'options'       => $this->options,
            'order'         => $this->order,
        ];
    }
}
