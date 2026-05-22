<?php

namespace App\Http\Requests\User\OnlineCourse;

use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_online_id' => ['required', 'integer', 'exists:course_onlines,id'],
            'content_id'       => ['required', 'integer', 'exists:module_contents,id'],
            'content_type'     => ['required', 'in:video,pdf'],
        ];
    }
}
