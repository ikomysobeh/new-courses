<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAudioProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_key' => ['sometimes', 'string', 'max:120'],
            'chunks' => ['required', 'array', 'min:1', 'max:300'],
            'chunks.*.current_time' => ['required', 'numeric', 'min:0'],
            'chunks.*.listened_time' => ['required', 'integer', 'min:0', 'max:3600'],
        ];
    }
}
