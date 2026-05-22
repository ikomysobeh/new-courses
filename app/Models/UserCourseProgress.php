<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCourseProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_online_id',
        'progress_percentage',
        'status',
        'total_content_items',
        'completed_content_items',
        'current_module_id',
        'started_at',
        'completed_at',
        'last_accessed_at',
        'last_session_id',
    ];

    protected function casts(): array
    {
        return [
            'progress_percentage' => 'decimal:2',
            'started_at'          => 'datetime',
            'completed_at'        => 'datetime',
            'last_accessed_at'    => 'datetime',
            'status'              => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    public function currentModule(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'current_module_id');
    }

    public function lastSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class, 'last_session_id');
    }
}
