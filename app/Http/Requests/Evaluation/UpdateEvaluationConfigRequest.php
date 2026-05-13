<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name'       => ['sometimes', 'string', 'max:255', Rule::unique('evaluation_configs', 'name')->ignore($id)],
            'max_score'  => ['sometimes', 'integer', 'min:1'],
            'applies_to' => ['sometimes', 'in:regular,online,both'],
        ];
    }
}
