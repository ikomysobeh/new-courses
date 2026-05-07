<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in'  => ['nullable', 'date'],
            'clock_out' => ['nullable', 'date'],
            'rating'    => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment'   => ['nullable', 'string', 'max:1000'],
        ];
    }
}
