<?php

namespace App\Http\Requests\User\OnlineCourse;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePdfProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_id'       => ['required', 'integer', 'exists:module_contents,id'],
            'course_online_id' => ['required', 'integer', 'exists:course_onlines,id'],
            'pages_viewed'     => ['required', 'integer', 'min:0'],
            'total_pages'      => ['required', 'integer', 'min:1'],
            'current_page'     => ['required', 'integer', 'min:1'],
        ];
    }
}
