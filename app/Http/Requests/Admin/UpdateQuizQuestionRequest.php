<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_text'              => ['sometimes', 'string'],
            'type'                       => ['sometimes', Rule::in(['radio', 'checkbox', 'text'])],
            'points'                     => ['sometimes', 'nullable', 'integer', 'min:0'],
            'options'                    => ['sometimes', 'nullable', 'array'],
            'options.*'                  => ['string'],
            'correct_answer'             => ['sometimes', 'nullable', 'array'],
            'correct_answer.*'           => ['string'],
            'correct_answer_explanation' => ['sometimes', 'nullable', 'string'],
            'order'                      => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');

            if ($type === null) {
                return;
            }

            if (in_array($type, ['radio', 'checkbox'])) {
                if ($this->has('options') && count($this->input('options', [])) === 0) {
                    $validator->errors()->add('options', 'Options cannot be empty for radio and checkbox questions.');
                }
                if ($this->has('correct_answer') && count($this->input('correct_answer', [])) === 0) {
                    $validator->errors()->add('correct_answer', 'correct_answer cannot be empty for radio and checkbox questions.');
                }
            }

            if ($type === 'text') {
                if ($this->filled('options')) {
                    $validator->errors()->add('options', 'Options must be null for text questions.');
                }
                if ($this->filled('correct_answer')) {
                    $validator->errors()->add('correct_answer', 'correct_answer must be null for text questions.');
                }
            }
        });
    }
}
