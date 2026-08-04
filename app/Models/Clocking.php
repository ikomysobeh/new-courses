<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clocking extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'user_id',
        'course_id',
        'clock_in',
        'clock_out',
        'duration_in_minutes',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'rating' => 'integer',
            'duration_in_minutes' => 'integer',
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
}
