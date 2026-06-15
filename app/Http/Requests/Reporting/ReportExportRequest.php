<?php

namespace App\Http\Requests\Reporting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from'       => ['nullable', 'date'],
            'date_to'         => ['nullable', 'date', 'after_or_equal:date_from'],
            'user_id'         => ['nullable', 'integer', 'exists:users,id'],
            'course_online_id'=> ['nullable', 'integer', 'exists:course_onlines,id'],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'export_type'     => ['required', Rule::in(['csv', 'xlsx'])],
        ];
    }
}
