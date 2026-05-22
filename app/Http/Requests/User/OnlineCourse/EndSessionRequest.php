<?php

namespace App\Http\Requests\User\OnlineCourse;

use Illuminate\Foundation\Http\FormRequest;

class EndSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'active_playback_time'  => ['required', 'integer', 'min:0'],
            'wall_clock_time'       => ['required', 'integer', 'min:0'],
            'playback_position'     => ['required', 'numeric', 'min:0'],
            'completion_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'skip_count'            => ['nullable', 'integer', 'min:0'],
            'seek_count'            => ['nullable', 'integer', 'min:0'],
            'replay_count'          => ['nullable', 'integer', 'min:0'],
            'pause_count'           => ['nullable', 'integer', 'min:0'],
            'speed_changes'         => ['nullable', 'integer', 'min:0'],
            'fullscreen_count'      => ['nullable', 'integer', 'min:0'],
            'events_log'            => ['nullable', 'array', 'max:50'],
            'events_log.*.type'     => ['nullable', 'string', 'in:pause,resume,skip,seek,milestone'],
            'events_log.*.at'       => ['nullable', 'integer', 'min:0'],
            'events_log.*.from'     => ['nullable', 'integer', 'min:0'],
            'events_log.*.to'       => ['nullable', 'integer', 'min:0'],
            'events_log.*.pct'      => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
