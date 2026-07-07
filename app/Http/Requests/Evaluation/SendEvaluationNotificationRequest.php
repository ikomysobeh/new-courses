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
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'exists:users,id'],
            'subject'    => ['required', 'string', 'max:255'],
            'message'    => ['required', 'string', 'max:2000'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date'   => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('start_date');
            $end   = $this->input('end_date');

            if ($start && $end && strtotime($start) > strtotime($end)) {
                $validator->errors()->add('end_date', 'End date must be on or after start date.');
            }
        });
    }
}
