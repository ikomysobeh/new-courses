<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class CourseModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'legacy_id',
        'course_online_id',
        'name',
        'description',
        'order_number',
        'estimated_duration',
        'has_quiz',
        'quiz_required',
    ];

    protected function casts(): array
    {
        return [
            'has_quiz'     => 'boolean',
            'quiz_required' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class, 'course_online_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(ModuleContent::class, 'module_id')->orderBy('order_number');
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class, 'module_id');
    }

    public function isCompletedByUser(int $userId): bool
    {
        $requiredContentIds = $this->contents()
            ->where('is_required', true)
            ->pluck('id');

        if ($requiredContentIds->isEmpty()) {
            $contentCompleted = true;
        } else {
            $completedCount = UserContentProgress::where('user_id', $userId)
                ->whereIn('content_id', $requiredContentIds)
                ->where('is_completed', true)
                ->count();

            $contentCompleted = $completedCount >= $requiredContentIds->count();
        }

        if (!$contentCompleted) {
            return false;
        }

        if ($this->has_quiz && $this->quiz_required) {
            $quiz = $this->quiz;
            if (!$quiz) {
                return false;
            }
            $passed = QuizAttempt::where('user_id', $userId)
                ->where('quiz_id', $quiz->id)
                ->where('passed', true)
                ->exists();

            return $passed;
        }

        return true;
    }
}
