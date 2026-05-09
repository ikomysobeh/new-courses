<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VideoChunkUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chunk'             => ['required', 'file'],
            'upload_uuid'       => ['required', 'string'],
            'chunk_index'       => ['required', 'integer', 'min:0'],
            'total_chunks'      => ['required', 'integer', 'min:1'],
            'original_filename' => ['required_if:chunk_index,0', 'nullable', 'string', 'max:255'],
        ];
    }
}
