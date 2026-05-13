<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_name'   => ['required', 'string', 'max:255'],
            'score_value' => ['required', 'integer', 'min:0'],
        ];
    }
}
