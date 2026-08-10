<?php

namespace App\Http\Requests\AttentionScore;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttentionScoreConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:255'],
            'config' => ['required', 'array'],

            'config.video.weights.watch_time' => ['required', 'numeric'],
            'config.video.weights.engagement' => ['required', 'numeric'],
            'config.video.weights.completion' => ['required', 'numeric'],

            'config.video.time_ratio_bands'   => ['required', 'array', 'min:1'],
            'config.video.time_ratio_bands.*.min'    => ['required', 'numeric', 'min:0'],
            'config.video.time_ratio_bands.*.max'    => ['nullable', 'numeric'],
            'config.video.time_ratio_bands.*.points' => ['required', 'numeric'],

            'config.video.engagement_base_points' => ['required', 'numeric'],

            'config.video.speed_change_bands'   => ['required', 'array', 'min:1'],
            'config.video.speed_change_bands.*.min'        => ['required', 'numeric', 'min:0'],
            'config.video.speed_change_bands.*.max'        => ['nullable', 'numeric'],
            'config.video.speed_change_bands.*.adjustment' => ['required', 'numeric', 'lte:0'],

            'config.video.completion_bands'   => ['required', 'array', 'min:1'],
            'config.video.completion_bands.*.min'    => ['required', 'numeric', 'min:0'],
            'config.video.completion_bands.*.max'    => ['nullable', 'numeric'],
            'config.video.completion_bands.*.points' => ['required', 'numeric'],

            'config.video.skip_ratio_bands'   => ['required', 'array', 'min:1'],
            'config.video.skip_ratio_bands.*.max'        => ['nullable', 'numeric'],
            'config.video.skip_ratio_bands.*.adjustment' => ['required', 'numeric', 'lte:0'],

            'config.video.consistency_validation.completion_threshold' => ['required', 'numeric', 'between:0,100'],
            'config.video.consistency_validation.skip_ratio_threshold' => ['required', 'numeric', 'min:0'],
            'config.video.consistency_validation.penalty'              => ['required', 'numeric', 'lte:0'],

            'config.video.allowed_review_window_multiplier' => ['required', 'numeric', 'min:1'],

            'config.risk_levels.high_below'   => ['required', 'numeric', 'between:0,100'],
            'config.risk_levels.medium_below' => ['required', 'numeric', 'between:0,100'],

            'config.blended_score_weights.completion'                    => ['required', 'numeric'],
            'config.blended_score_weights.progress'                       => ['required', 'numeric'],
            'config.blended_score_weights.attention'                      => ['required', 'numeric'],
            'config.blended_score_weights.quiz'                           => ['required', 'numeric'],
            'config.blended_score_weights.suspicious_penalty_multiplier' => ['required', 'numeric', 'min:0'],
        ];
    }
}
