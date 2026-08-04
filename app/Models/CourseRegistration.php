<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'user_id',
        'course_id',
        'course_availability_id',
        'status',
        'registered_at',
        'completed_at',
        'rating',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'completed_at' => 'datetime',
            'rating' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(CourseAvailability::class, 'course_availability_id');
    }
}
