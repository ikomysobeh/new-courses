<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RespondFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_response' => ['required', 'string', 'max:1000'],
            'status'         => ['required', 'in:pending,under_review,approved,rejected'],
        ];
    }
}
