<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'assigned_by',
        'assigned_at',
        'notification_sent',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'notification_sent' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
