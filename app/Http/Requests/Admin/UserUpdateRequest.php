<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'email'              => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password'           => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'role'               => ['sometimes', 'string', 'in:admin,user'],
            'department_id'      => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            // A user can report to 1 or 2 managers. Legacy single "report_to" still accepted.
            'report_to'          => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'manager_ids'        => ['sometimes', 'nullable', 'array', 'max:2'],
            'manager_ids.*'      => ['integer', 'distinct', 'exists:users,id'],
            'user_level_tier_id' => ['sometimes', 'nullable', 'integer', 'exists:user_level_tiers,id'],
        ];
    }
}
