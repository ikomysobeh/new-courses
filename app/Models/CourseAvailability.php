<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'course_id',
        'start_date',
        'end_date',
        'capacity',
        'sessions',
        'duration_weeks',
        'status',
        'notes',
        'days_of_week',
        'session_time_shift_1',
        'session_time_shift_2',
        'session_time_shift_3',
        'session_duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'capacity' => 'integer',
            'sessions' => 'integer',
            'duration_weeks' => 'integer',
            'session_duration_minutes' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
