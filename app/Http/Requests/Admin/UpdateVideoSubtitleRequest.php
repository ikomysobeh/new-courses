<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVideoSubtitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subtitle_file' => ['required', 'file', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $file = $this->file('subtitle_file');
            if ($file && strtolower($file->getClientOriginalExtension()) !== 'vtt') {
                $validator->errors()->add('subtitle_file', 'The subtitle file must be a .vtt file.');
            }
        });
    }
}
