<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportingUserCourseDaily extends Model
{
    protected $table = 'reporting_user_course_daily';

    protected $fillable = [
        'user_id',
        'course_online_id',
        'department_id',
        'report_date',
        'sessions_count',
        'active_playback_time',
        'content_items_completed',
        'course_progress_pct',
    ];

    protected $casts = [
        'report_date'             => 'date',
        'sessions_count'          => 'integer',
        'active_playback_time'    => 'integer',
        'content_items_completed' => 'integer',
        'course_progress_pct'     => 'decimal:2',
    ];

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
}
