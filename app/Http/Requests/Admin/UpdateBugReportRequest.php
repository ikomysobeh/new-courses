<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBugReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority'    => ['nullable', 'in:low,medium,high,critical'],
            'status'      => ['nullable', 'in:open,in_progress,resolved,closed'],
            'assigned_to' => [
                'nullable',
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
