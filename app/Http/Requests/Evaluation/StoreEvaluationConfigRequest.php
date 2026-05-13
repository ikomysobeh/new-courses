<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255', 'unique:evaluation_configs,name'],
            'max_score'  => ['required', 'integer', 'min:1'],
            'applies_to' => ['required', 'in:regular,online,both'],
        ];
    }
}
