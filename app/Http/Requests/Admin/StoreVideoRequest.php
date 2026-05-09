<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'video_category_id' => ['required', 'integer', 'exists:video_categories,id'],
            'file_path'         => ['required', 'string'],
            'file_size'         => ['nullable', 'integer', 'min:0'],
            'duration_seconds'  => ['nullable', 'integer', 'min:0'],
            'thumbnail_path'    => ['nullable', 'string'],
            'subtitle_vtt_path' => ['nullable', 'string'],
        ];
    }
}
