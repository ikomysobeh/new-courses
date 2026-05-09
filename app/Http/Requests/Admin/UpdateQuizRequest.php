<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                => ['sometimes', 'string', 'max:255'],
            'description'          => ['sometimes', 'nullable', 'string'],
            'required_to_proceed'  => ['sometimes', 'boolean'],
            'max_attempts'         => ['sometimes', 'integer', 'min:1'],
            'retry_delay_hours'    => ['sometimes', 'integer', 'min:0'],
            'show_correct_answers' => ['sometimes', Rule::in(['never', 'after_pass', 'after_max_attempts', 'always'])],
            'deadline'             => ['sometimes', 'nullable', 'date'],
            'time_limit_minutes'   => ['sometimes', 'nullable', 'integer', 'min:1'],
            'status'               => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'pass_threshold'       => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
