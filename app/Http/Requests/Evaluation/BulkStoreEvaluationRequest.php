<?php

namespace App\Http\Requests\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluations'                               => ['required', 'array', 'min:1'],
            'evaluations.*.user_id'                     => ['required', 'integer', 'exists:users,id'],
            'evaluations.*.department_id'               => ['required', 'integer', 'exists:departments,id'],
            'evaluations.*.course_type'                 => ['required', 'in:regular,online'],
            'evaluations.*.course_id'                   => ['nullable', 'integer', 'exists:courses,id'],
            'evaluations.*.course_online_id'            => ['nullable', 'integer', 'exists:course_onlines,id'],
            'evaluations.*.scores'                      => ['required', 'array', 'min:1'],
            'evaluations.*.scores.*.evaluation_type_id' => ['required', 'integer', 'exists:evaluation_types,id'],
            'evaluations.*.scores.*.score_given'        => ['required', 'integer', 'min:0'],
        ];
    }
}
