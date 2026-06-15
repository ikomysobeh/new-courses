<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class UserQuizAnswerResource extends BaseResource
{
    public bool $includeIsCorrect = false;

    public function toArray(Request $request): array
    {
        $data = [
            'id'               => $this->id,
            'quiz_question_id' => $this->quiz_question_id,
            'answer'           => $this->answer,
            'points_earned'    => $this->points_earned,
        ];

        if ($this->includeIsCorrect) {
            $data['is_correct'] = $this->is_correct;
        }

        return $data;
    }
}
