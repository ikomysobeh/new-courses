<?php

namespace App\Http\Requests\Admin\OnlineCourse;

use Illuminate\Foundation\Http\FormRequest;

class UploadCourseVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video'             => ['required', 'file', 'mimes:mp4,mov,avi,mkv,webm', 'max:2097152'], // 2 GB
            'name'              => ['nullable', 'string', 'max:255'],
            'video_category_id' => ['nullable', 'integer', 'exists:video_categories,id'],
        ];
    }
}
