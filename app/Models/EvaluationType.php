<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationType extends Model
{
    protected $fillable = [
        'legacy_id',
        'evaluation_config_id',
        'type_name',
        'score_value',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(EvaluationConfig::class, 'evaluation_config_id');
    }
}
