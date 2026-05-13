<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scores'                      => ['required', 'array', 'min:1'],
            'scores.*.evaluation_type_id' => ['required', 'integer', 'exists:evaluation_types,id'],
            'scores.*.score_given'        => ['required', 'integer', 'min:0'],
        ];
    }
}
