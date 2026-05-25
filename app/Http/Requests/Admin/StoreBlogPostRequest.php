<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'slug'         => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', Rule::unique('podcast_posts', 'slug')],
            'excerpt'      => ['nullable', 'string', 'max:500'],
            'description'  => ['nullable', 'string'],
            'status'       => ['nullable', Rule::in(['draft', 'published'])],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['string', 'max:50'],
            'mediable_type'=> ['nullable', 'required_with:mediable_id', 'string', Rule::in(['App\\Models\\Video', 'App\\Models\\Audio'])],
            'mediable_id'  => ['nullable', 'required_with:mediable_type', 'integer', 'min:1'],
            'thumbnail'    => ['nullable', 'image', 'max:4096'],
        ];
    }
}
