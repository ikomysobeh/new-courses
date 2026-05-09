<?php

namespace App\Http\Requests\Admin\OnlineCourse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOnlineCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['sometimes', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'image_path'         => ['nullable', 'string', 'max:500'],
            'level'              => ['nullable', 'in:beginner,intermediate,advanced'],
            'estimated_duration' => ['nullable', 'integer', 'min:1'],
            'status'             => ['nullable', 'in:draft,published,archived'],
            'is_active'          => ['nullable', 'boolean'],
            'deadline'           => ['nullable', 'date'],

            // Full module tree to sync (replaces current modules)
            'modules'                             => ['nullable', 'array'],
            'modules.*.id'                        => ['nullable', 'integer', 'exists:course_modules,id'],
            'modules.*.name'                      => ['required_with:modules', 'string', 'max:255'],
            'modules.*.description'               => ['nullable', 'string'],
            'modules.*.order_number'              => ['required_with:modules', 'integer', 'min:1'],
            'modules.*.estimated_duration'        => ['nullable', 'integer', 'min:1'],
            'modules.*.has_quiz'                  => ['nullable', 'boolean'],
            'modules.*.quiz_required'             => ['nullable', 'boolean'],

            'modules.*.contents'                         => ['nullable', 'array'],
            'modules.*.contents.*.id'                    => ['nullable', 'integer', 'exists:module_contents,id'],
            'modules.*.contents.*.name'                  => ['required_with:modules.*.contents', 'string', 'max:255'],
            'modules.*.contents.*.description'           => ['nullable', 'string'],
            'modules.*.contents.*.content_type'          => ['required_with:modules.*.contents', 'in:video,pdf,audio,text'],
            'modules.*.contents.*.order_number'          => ['required_with:modules.*.contents', 'integer', 'min:1'],
            'modules.*.contents.*.content_id'            => ['nullable', 'integer'],
            'modules.*.contents.*.text_body'             => ['nullable', 'string'],
            'modules.*.contents.*.estimated_duration'    => ['nullable', 'integer', 'min:1'],
        ];
    }
}
