<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_online_id',
        'total_enrollments',
        'total_completions',
        'total_modules',
        'total_contents',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }
}
