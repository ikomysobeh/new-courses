<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'course_online_id',
        'total_enrollments',
        'active_learners',
        'completed_learners',
        'completion_rate',
        'dropout_rate',
        'average_session_duration_minutes',
        'average_video_completion_rate',
        'cheating_incidents_count',
        'last_calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'completion_rate'              => 'float',
            'dropout_rate'                => 'float',
            'average_video_completion_rate' => 'float',
            'last_calculated_at'          => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }
}
