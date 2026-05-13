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
            'order'                  => ['required', 'array', 'min:1'],
            'order.*.module_id'      => ['required', 'integer', 'exists:course_modules,id'],
            'order.*.order_number'   => ['required', 'integer', 'min:1'],
        ];
    }
}
