<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_text'              => ['required', 'string'],
            'type'                       => ['required', Rule::in(['radio', 'checkbox', 'text'])],
            'points'                     => ['nullable', 'integer', 'min:0'],
            'options'                    => ['nullable', 'array'],
            'options.*'                  => ['string'],
            'correct_answer'             => ['nullable', 'array'],
            'correct_answer.*'           => ['string'],
            'correct_answer_explanation' => ['nullable', 'string'],
            'order'                      => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');

            if (in_array($type, ['radio', 'checkbox'])) {
                if (!$this->filled('options') || count($this->input('options', [])) === 0) {
                    $validator->errors()->add('options', 'Options are required for radio and checkbox questions.');
                }
                if (!$this->filled('correct_answer') || count($this->input('correct_answer', [])) === 0) {
                    $validator->errors()->add('correct_answer', 'correct_answer is required for radio and checkbox questions.');
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
