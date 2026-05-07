<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id'               => ['required', 'integer', 'exists:courses,id'],
            'user_id'                 => ['required', 'integer', 'exists:users,id'],
            'course_availability_id'  => ['nullable', 'integer', 'exists:course_availabilities,id'],
        ];
    }
}
