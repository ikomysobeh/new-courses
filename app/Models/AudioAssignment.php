<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AudioAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'audio_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'notification_sent',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'notification_sent' => 'boolean',
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
