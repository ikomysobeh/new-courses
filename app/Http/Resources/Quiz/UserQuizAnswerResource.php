<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class UserQuizAnswerResource extends BaseResource
{
    /**
     * Whether the actual correct answer + explanation may be revealed
     * (controlled by the quiz's show_correct_answers setting). The student's
     * own correctness (is_correct) is always exposed so choice questions can be
     * shown as correct/incorrect; only open-text answers stay pending.
     */
    public bool $includeCorrectAnswer = false;

    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'quiz_question_id' => $this->quiz_question_id,
            'answer'           => $this->answer,
            'points_earned'    => $this->points_earned,
            // null = not yet graded (open-text awaiting manual review); the
            // frontend treats null as "Under Review".
            'is_correct'       => $this->is_correct,
            'question'         => $this->whenLoaded('question', function () {
                $q = $this->question;
                $data = [
                    'id'            => $q->id,
                    'question_text' => $q->question_text,
                    'type'          => $q->type,
                    'points'        => $q->points,
                    'options'       => $q->options,
                ];

                if ($this->includeCorrectAnswer) {
                    $data['correct_answer']             = $q->correct_answer;
                    $data['correct_answer_explanation'] = $q->correct_answer_explanation;
                }

                return $data;
            }),
        ];
    }
}
