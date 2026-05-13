<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class SendEvaluationNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manager_ids'   => ['required', 'array', 'min:1'],
            'manager_ids.*' => ['required', 'integer', 'exists:users,id'],
            'subject'       => ['required', 'string', 'max:255'],
            'message'       => ['required', 'string', 'max:2000'],
            'start_date'    => ['sometimes', 'nullable', 'date', 'before_or_equal:end_date'],
            'end_date'      => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
