<?php

namespace App\Http\Requests\AttentionScore;

class PreviewAttentionScoreConfigRequest extends StoreAttentionScoreConfigRequest
{
    public function rules(): array
    {
        // Same shape as saving a config, minus the "name" (a preview isn't persisted).
        return array_merge(parent::rules(), [
            'name' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
