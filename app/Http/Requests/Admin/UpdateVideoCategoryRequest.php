<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVideoCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name'       => ['required', 'string', 'max:255', Rule::unique('video_categories', 'name')->ignore($id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
