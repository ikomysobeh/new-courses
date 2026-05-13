<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'          => ['required', 'integer', 'exists:users,id'],
            'department_id'    => ['required', 'integer', 'exists:departments,id'],
            'course_type'      => ['required', 'in:regular,online'],
            'course_id'        => ['required_if:course_type,regular', 'nullable', 'integer', 'exists:courses,id'],
            'course_online_id' => ['required_if:course_type,online', 'nullable', 'integer', 'exists:course_onlines,id'],
            'scores'           => ['required', 'array', 'min:1'],
            'scores.*.evaluation_type_id' => ['required', 'integer', 'exists:evaluation_types,id'],
            'scores.*.score_given'        => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required_if'        => 'A course ID is required for regular course evaluations.',
            'course_online_id.required_if' => 'An online course ID is required for online course evaluations.',
        ];
    }
}
