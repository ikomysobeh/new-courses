<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignBugReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'admin'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'The assigned user must be an admin.',
        ];
    }
}
