<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'audio_category_id' => ['sometimes', 'required', 'integer', 'exists:audio_categories,id'],
            'duration' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'audio_file' => ['sometimes', 'nullable', 'file', 'mimes:mp3,wav,aac,ogg,m4a', 'max:102400'],
            'thumbnail' => ['sometimes', 'nullable', 'image', 'max:4096'],
        ];
    }
}
