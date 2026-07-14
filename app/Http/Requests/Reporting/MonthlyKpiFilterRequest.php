<?php

namespace App\Http\Requests\Reporting;

use Illuminate\Foundation\Http\FormRequest;

class MonthlyKpiFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year'             => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month'            => ['nullable', 'integer', 'min:1', 'max:12'],
            'department_id'    => ['nullable', 'integer', 'exists:departments,id'],
            'course_online_id' => ['nullable', 'integer', 'exists:course_onlines,id'],
        ];
    }
}
