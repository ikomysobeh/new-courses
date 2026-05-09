<?php

namespace App\Http\Requests\Admin\OnlineCourse;

use Illuminate\Foundation\Http\FormRequest;

class ReorderModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_online_id'           => ['required', 'integer', 'exists:course_onlines,id'],
            'modules'                    => ['required', 'array', 'min:1'],
            'modules.*.id'               => ['required', 'integer', 'exists:course_modules,id'],
            'modules.*.order_number'     => ['required', 'integer', 'min:1'],
        ];
    }
}
