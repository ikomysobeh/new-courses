<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'user_id',
        'course_online_id',
        'content_id',
        'content_type',
        'session_start',
        'session_end',
        'last_progress_at',
        'active_playback_time',
        'wall_clock_seconds',
        'skip_count',
        'seek_count',
        'replay_count',
        'pause_count',
        'speed_changes',
        'fullscreen_count',
        'video_completion_percentage',
        'attention_score',
        'attention_score_config_id',
        'is_suspicious',
        'events_log',
        'watched_segments',
        'last_played_position',
        'unwatched_seconds_skipped',
    ];

    protected function casts(): array
    {
        return [
            'session_start'               => 'datetime',
            'session_end'                 => 'datetime',
            'last_progress_at'            => 'datetime',
            'video_completion_percentage' => 'decimal:2',
            'is_suspicious'               => 'boolean',
            'attention_score'             => 'integer',
            'events_log'                  => 'array',
            'watched_segments'            => 'array',
            'last_played_position'        => 'float',
            'unwatched_seconds_skipped'   => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attentionScoreConfig(): BelongsTo
    {
        return $this->belongsTo(AttentionScoreConfig::class, 'attention_score_config_id');
    }

    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(ModuleContent::class, 'content_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('session_end');
    }
}
