<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['nullable', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'video_category_id' => ['nullable', 'integer', 'exists:video_categories,id'],
            'file_size'         => ['nullable', 'integer', 'min:0'],
            'duration_seconds'  => ['nullable', 'integer', 'min:0'],
            'thumbnail'         => ['sometimes', 'nullable', 'image', 'max:4096'],
            'thumbnail_path'    => ['nullable', 'string', 'max:500'],
        ];
    }
}
