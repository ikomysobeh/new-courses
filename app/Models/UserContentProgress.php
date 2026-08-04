<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserContentProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'user_id',
        'content_id',
        'course_online_id',
        'module_id',
        'content_type',
        'watch_time',
        'pdf_pages_viewed',
        'completion_percentage',
        'is_completed',
        'completed_at',
        'last_accessed_at',
        'playback_position',
    ];

    protected function casts(): array
    {
        return [
            'is_completed'          => 'boolean',
            'completed_at'          => 'datetime',
            'last_accessed_at'      => 'datetime',
            'completion_percentage' => 'decimal:2',
            'playback_position'     => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(ModuleContent::class, 'content_id');
    }

    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }
}
