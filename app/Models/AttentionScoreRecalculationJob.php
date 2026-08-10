<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttentionScoreRecalculationJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'attention_score_config_id',
        'status',
        'total_sessions',
        'processed_sessions',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(AttentionScoreConfig::class, 'attention_score_config_id');
    }
}
