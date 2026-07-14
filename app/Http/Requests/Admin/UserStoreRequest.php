<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'           => ['required', 'string', 'min:8', 'confirmed'],
            'role'               => ['sometimes', 'string', 'in:admin,user'],
            'department_id'      => ['nullable', 'integer', 'exists:departments,id'],
            // A user can report to 1 or 2 managers. Legacy single "report_to" still accepted.
            'report_to'          => ['nullable', 'integer', 'exists:users,id'],
            'manager_ids'        => ['nullable', 'array', 'max:2'],
            'manager_ids.*'      => ['integer', 'distinct', 'exists:users,id'],
            'user_level_tier_id' => ['nullable', 'integer', 'exists:user_level_tiers,id'],
        ];
    }
}
