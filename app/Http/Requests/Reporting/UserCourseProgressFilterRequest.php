<?php

namespace App\Http\Requests\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class UserCourseProgressFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from'        => ['nullable', 'date'],
            'date_to'          => ['nullable', 'date', 'after_or_equal:date_from'],
            'user_id'          => ['nullable', 'integer', 'exists:users,id'],
            'department_id'    => ['nullable', 'integer', 'exists:departments,id'],
            'course_online_id' => ['nullable', 'integer', 'exists:course_onlines,id'],
            'status'           => ['nullable', 'in:not_started,in_progress,completed'],
            'per_page'         => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
