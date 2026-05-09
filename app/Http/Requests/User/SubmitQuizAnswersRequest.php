<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers'                      => ['required', 'array', 'min:1'],
            'answers.*.quiz_question_id'   => ['required', 'integer', 'exists:quiz_questions,id'],
            'answers.*.answer'             => ['required', 'string', 'min:1'],
        ];
    }
}
