<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationSend extends Model
{
    protected $fillable = [
        'type',
        'subject',
        'message',
        'recipient_ids',
        'evaluation_ids',
        'status',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'recipient_ids'  => 'array',
            'evaluation_ids' => 'array',
            'sent_at'        => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }
}
