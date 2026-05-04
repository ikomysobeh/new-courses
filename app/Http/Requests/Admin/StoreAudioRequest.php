<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'audio_category_id' => ['required', 'integer', 'exists:audio_categories,id'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'audio_file' => ['nullable', 'file', 'mimes:mp3,wav,aac,ogg,m4a', 'max:102400'],
            'thumbnail' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
