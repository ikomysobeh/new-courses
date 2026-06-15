<?php

namespace App\Http\Requests\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class CourseRegistrationFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from'     => ['nullable', 'date'],
            'date_to'       => ['nullable', 'date', 'after_or_equal:date_from'],
            'course_id'     => ['nullable', 'integer', 'exists:courses,id'],
            'user_id'       => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status'        => ['nullable', 'in:pending,in_progress,completed'],
            'per_page'      => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
