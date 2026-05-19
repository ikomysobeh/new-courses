<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBugReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'description'         => ['required', 'string'],
            'priority'            => ['required', 'in:low,medium,high,critical'],
            'steps_to_reproduce'  => ['nullable', 'string'],
            'page_url'            => ['nullable', 'url', 'max:255'],
        ];
    }
}
