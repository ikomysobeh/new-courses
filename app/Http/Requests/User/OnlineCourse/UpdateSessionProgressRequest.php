<?php

namespace App\Http\Requests\User\OnlineCourse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'active_playback_time'  => ['required', 'integer', 'min:0'],
            'playback_position'     => ['required', 'numeric', 'min:0'],
            'completion_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'skip_count'            => ['nullable', 'integer', 'min:0'],
            'seek_count'            => ['nullable', 'integer', 'min:0'],
            'replay_count'          => ['nullable', 'integer', 'min:0'],
            'pause_count'           => ['nullable', 'integer', 'min:0'],
            'speed_changes'         => ['nullable', 'integer', 'min:0'],
        ];
    }
}
