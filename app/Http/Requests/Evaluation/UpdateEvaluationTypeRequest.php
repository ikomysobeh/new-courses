<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_name'   => ['sometimes', 'string', 'max:255'],
            'score_value' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
