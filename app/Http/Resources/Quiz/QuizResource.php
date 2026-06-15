<?php

namespace App\Http\Resources\Quiz;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class QuizResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'description'          => $this->description,
            'course_id'            => $this->course_id,
            'course_name'          => $this->whenLoaded('course', fn () => $this->course?->name),
            'course_online_id'     => $this->course_online_id ?? $this->whenLoaded('module', fn () => $this->module?->course_online_id),
            'module_id'            => $this->module_id,
            'status'               => $this->status,
            'required_to_proceed'  => (bool) $this->required_to_proceed,
            'max_attempts'         => $this->max_attempts,
            'retry_delay_hours'    => $this->retry_delay_hours,
            'show_correct_answers' => $this->show_correct_answers,
            'deadline'             => $this->deadline,
            'time_limit_minutes'   => $this->time_limit_minutes,
            'total_points'         => $this->total_points,
            'pass_threshold'       => $this->pass_threshold,
            'questions_count'      => $this->when(
                $this->relationLoaded('questions') || isset($this->questions_count),
                fn () => $this->relationLoaded('questions')
                    ? $this->questions->count()
                    : $this->questions_count
            ),
            'questions'            => $this->whenLoaded(
                'questions',
                fn () => QuizQuestionResource::collection($this->questions)
            ),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
