<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AudioProgress extends Model
{
    use HasFactory;

    protected $table = 'audio_progress';

    protected $fillable = [
        'user_id',
        'audio_id',
        'current_time',
        'total_listened_time',
        'is_completed',
        'completion_percentage',
        'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'current_time' => 'decimal:2',
            'total_listened_time' => 'integer',
            'is_completed' => 'boolean',
            'completion_percentage' => 'decimal:2',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function audio(): BelongsTo
    {
        return $this->belongsTo(Audio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
