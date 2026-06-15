<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingLearningSessionsFact extends Model
{
    protected $table = 'reporting_learning_sessions_fact';

    // append-only table — no updated_at
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'user_id',
        'course_online_id',
        'content_id',
        'department_id',
        'session_date',
        'active_playback_time',
        'wall_clock_seconds',
        'completion_percentage',
        'attention_score',
        'is_suspicious',
        'skip_count',
        'seek_count',
        'replay_count',
        'pause_count',
        'content_completed',
        'created_at',
    ];

    protected $casts = [
        'session_date'          => 'date',
        'active_playback_time'  => 'integer',
        'wall_clock_seconds'    => 'integer',
        'completion_percentage' => 'decimal:2',
        'attention_score'       => 'integer',
        'is_suspicious'         => 'boolean',
        'skip_count'            => 'integer',
        'seek_count'            => 'integer',
        'replay_count'          => 'integer',
        'pause_count'           => 'integer',
        'content_completed'     => 'boolean',
        'created_at'            => 'datetime',
    ];

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(LearningSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(ModuleContent::class, 'content_id');
    }
}
