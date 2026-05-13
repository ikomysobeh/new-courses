<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseOnlineAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_online_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'deadline',
        'is_overdue',
        'deadline_notification_sent_at',
        'unassigned_at',
        'unassigned_by',
    ];

    protected function casts(): array
    {
        return [
            'is_overdue'                    => 'boolean',
            'assigned_at'                   => 'datetime',
            'deadline'                      => 'datetime',
            'deadline_notification_sent_at' => 'datetime',
            'unassigned_at'                 => 'datetime',
            'deleted_at'                    => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
