<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'        => ['required', 'in:suggestion,improvement,feature_request,general'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
