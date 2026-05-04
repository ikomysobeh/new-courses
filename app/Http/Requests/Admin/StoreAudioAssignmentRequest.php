<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAudioAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'audio_id' => ['required', 'integer', 'exists:audios,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'send_notification' => ['sometimes', 'boolean'],
        ];
    }
}
