<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->availabilities)) {
            $decoded = json_decode($this->availabilities, true);
            if (is_array($decoded)) {
                // Also decode days_of_week inside each availability if it arrived as a string
                $decoded = array_map(function ($avail) {
                    if (isset($avail['days_of_week']) && is_string($avail['days_of_week'])) {
                        $days = json_decode($avail['days_of_week'], true);
                        $avail['days_of_week'] = is_array($days) ? $days : [];
                    }

                    return $avail;
                }, $decoded);
                $this->merge(['availabilities' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'image'             => ['nullable', 'image', 'max:4096'],
            'status'            => ['required', 'in:draft,published,archived'],
            'privacy'           => ['required', 'in:public,private'],
            'level'             => ['nullable', 'in:beginner,intermediate,advanced'],
            'duration'          => ['nullable', 'numeric', 'min:0'],

            'availabilities'    => ['required', 'array', 'min:1', 'max:5'],

            'availabilities.*.start_date'               => ['required', 'date'],
            'availabilities.*.end_date'                 => ['required', 'date', 'after:availabilities.*.start_date'],
            'availabilities.*.capacity'                 => ['required', 'integer', 'min:1'],
            'availabilities.*.sessions'                 => ['nullable', 'integer', 'min:0'],
            'availabilities.*.notes'                    => ['nullable', 'string'],
            'availabilities.*.days_of_week'             => ['nullable', 'array'],
            'availabilities.*.days_of_week.*'           => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'availabilities.*.duration_weeks'           => ['nullable', 'integer', 'min:1'],
            'availabilities.*.session_time_shift_1'     => ['nullable', 'date_format:H:i'],
            'availabilities.*.session_time_shift_2'     => ['nullable', 'date_format:H:i'],
            'availabilities.*.session_time_shift_3'     => ['nullable', 'date_format:H:i'],
            'availabilities.*.session_duration_minutes' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
