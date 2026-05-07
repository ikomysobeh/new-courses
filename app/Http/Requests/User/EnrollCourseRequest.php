<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class EnrollCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_availability_id' => ['required', 'integer', 'exists:course_availabilities,id'],
        ];
    }
}
