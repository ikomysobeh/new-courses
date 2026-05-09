<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'course_id'            => ['nullable', 'integer', 'exists:courses,id'],
            'course_online_id'     => ['nullable', 'integer'],
            'module_id'            => ['nullable', 'integer'],
            'required_to_proceed'  => ['nullable', 'boolean'],
            'max_attempts'         => ['nullable', 'integer', 'min:1'],
            'retry_delay_hours'    => ['nullable', 'integer', 'min:0'],
            'show_correct_answers' => ['nullable', Rule::in(['never', 'after_pass', 'after_max_attempts', 'always'])],
            'deadline'             => ['nullable', 'date'],
            'time_limit_minutes'   => ['nullable', 'integer', 'min:1'],
            'status'               => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'pass_threshold'       => ['nullable', 'numeric', 'min:0', 'max:100'],

            'questions'                              => ['nullable', 'array'],
            'questions.*.question_text'              => ['required_with:questions', 'string'],
            'questions.*.type'                       => ['required_with:questions', Rule::in(['radio', 'checkbox', 'text'])],
            'questions.*.points'                     => ['nullable', 'integer', 'min:0'],
            'questions.*.options'                    => ['nullable', 'array'],
            'questions.*.options.*'                  => ['string'],
            'questions.*.correct_answer'             => ['nullable', 'array'],
            'questions.*.correct_answer.*'           => ['string'],
            'questions.*.correct_answer_explanation' => ['nullable', 'string'],
            'questions.*.order'                      => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $filled = collect(['course_id', 'course_online_id', 'module_id'])
                ->filter(fn ($field) => $this->filled($field))
                ->count();

            if ($filled > 1) {
                $validator->errors()->add('ownership', 'Only one of course_id, course_online_id, or module_id may be provided.');
            }
        });
    }
}
