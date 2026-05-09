<?php

namespace App\Http\Requests\Admin\OnlineCourse;

use Illuminate\Foundation\Http\FormRequest;

class StoreOnlineCourseAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_online_id' => ['required', 'integer', 'exists:course_onlines,id'],
            'user_id'          => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
