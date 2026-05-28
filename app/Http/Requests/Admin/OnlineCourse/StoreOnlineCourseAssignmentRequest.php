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
            'course_online_id'  => ['required', 'integer', 'exists:course_onlines,id'],
            'user_ids'          => ['required', 'array', 'min:1'],
            'user_ids.*'        => ['required', 'integer', 'exists:users,id'],
            'send_notification' => ['nullable', 'boolean'],
        ];
    }
}
