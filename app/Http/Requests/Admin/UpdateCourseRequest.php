<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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
            'name'              => ['sometimes', 'required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'image'             => ['nullable', 'image', 'max:4096'],
            'status'            => ['sometimes', 'required', 'in:draft,published,archived'],
            'privacy'           => ['sometimes', 'required', 'in:public,private'],
            'level'             => ['nullable', 'in:beginner,intermediate,advanced'],
            'duration'          => ['nullable', 'numeric', 'min:0'],

            'availabilities'    => ['sometimes', 'required', 'array', 'min:1', 'max:5'],

            'availabilities.*.id'                       => ['nullable', 'integer', 'exists:course_availabilities,id'],
            'availabilities.*.start_date'               => ['required_without:availabilities.*.id', 'date'],
            'availabilities.*.end_date'                 => ['required_without:availabilities.*.id', 'date', 'after:availabilities.*.start_date'],
            'availabilities.*.capacity'                 => ['required_without:availabilities.*.id', 'integer', 'min:1'],
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
