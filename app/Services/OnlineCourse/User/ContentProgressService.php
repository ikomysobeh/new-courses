<?php

namespace App\Services\OnlineCourse\User;

use App\Models\CourseModule;
use App\Models\CourseOnlineAssignment;
use App\Models\ModuleContent;
use App\Models\QuizAttempt;
use App\Models\UserContentProgress;
use App\Models\UserCourseProgress;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContentProgressService
{
    public function recalculateCourseProgress(int $userId, int $courseOnlineId): void
    {
        // Load all required content IDs for the course in one query
        $requiredContentIds = ModuleContent::whereHas('module', function ($q) use ($courseOnlineId) {
            $q->where('course_online_id', $courseOnlineId);
        })
        ->where('is_required', true)
        ->pluck('id');

        $total = $requiredContentIds->count();

        if ($total === 0) {
            return;
        }

        // Load all user progress keyed by content_id in one query
        $progressMap = UserContentProgress::where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->whereIn('content_id', $requiredContentIds)
            ->where('is_completed', true)
            ->count();

        $completed = $progressMap;
        $percentage = round(($completed / $total) * 100, 2);

        if ($percentage > 100) {
            $percentage = 100.00;
        }

        $status = 'not_started';
        if ($percentage > 0 && $percentage < 100) {
            $status = 'in_progress';
        } elseif ($percentage >= 100) {
            $allQuizzesPassed = $this->allRequiredQuizzesPassed($userId, $courseOnlineId);
            $status = $allQuizzesPassed ? 'completed' : 'in_progress';
        }

        $updateData = [
            'total_content_items'     => $total,
            'completed_content_items' => $completed,
            'progress_percentage'     => $percentage,
            'status'                  => $status,
            'last_accessed_at'        => now(),
        ];

        if ($status === 'completed') {
            $existing = UserCourseProgress::where('user_id', $userId)
                ->where('course_online_id', $courseOnlineId)
                ->first();
            $updateData['completed_at'] = $existing?->completed_at ?? now();
        }

        UserCourseProgress::updateOrCreate(
            ['user_id' => $userId, 'course_online_id' => $courseOnlineId],
            $updateData
        );
    }

    private function allRequiredQuizzesPassed(int $userId, int $courseOnlineId): bool
    {
        $modules = CourseModule::where('course_online_id', $courseOnlineId)
            ->where('has_quiz', true)
            ->where('quiz_required', true)
            ->with('quiz')
            ->get();

        if ($modules->isEmpty()) {
            return true;
        }

        foreach ($modules as $module) {
            if (!$module->quiz) {
                return false;
            }

            $passed = QuizAttempt::where('user_id', $userId)
                ->where('quiz_id', $module->quiz->id)
                ->where('passed', true)
                ->exists();

            if (!$passed) {
                return false;
            }
        }

        return true;
    }

    public function updatePdfProgress(int $userId, array $data): array
    {
        $courseId  = $data['course_online_id'];
        $contentId = $data['content_id'];

        // Verify user is assigned
        $assigned = CourseOnlineAssignment::where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->exists();

        if (!$assigned) {
            abort(403, 'Not assigned to this course.');
        }

        // Verify content is PDF
        $content = ModuleContent::find($contentId);

        if (!$content || $content->content_type !== 'pdf') {
            abort(422, 'Content is not a PDF.');
        }

        // Load total pages from module_content_pdfs
        $totalPagesFromDb = $content->pdf?->pdf_page_count ?? $data['total_pages'];
        $totalPages       = $totalPagesFromDb ?: $data['total_pages'];

        // Load existing progress
        $existing = UserContentProgress::where('user_id', $userId)
            ->where('content_id', $contentId)
            ->first();

        $currentPagesViewed = $existing?->pdf_pages_viewed ?? 0;
        $newPagesViewed     = max($currentPagesViewed, $data['pages_viewed']);
        $completionPct      = round(($newPagesViewed / $totalPages) * 100, 2);
        $isCompleted        = $completionPct >= 100;

        $wasCompleted = $existing?->is_completed ?? false;

        $updateData = [
            'course_online_id'      => $courseId,
            'module_id'             => $content->module_id,
            'content_type'          => 'pdf',
            'pdf_pages_viewed'      => $newPagesViewed,
            'completion_percentage' => min(100, $completionPct),
            'playback_position'     => $data['current_page'],
            'last_accessed_at'      => now(),
        ];

        if ($isCompleted && !$wasCompleted) {
            $updateData['is_completed'] = true;
            $updateData['completed_at'] = now();
        }

        UserContentProgress::updateOrCreate(
            ['user_id' => $userId, 'content_id' => $contentId],
            $updateData
        );

        // Recalculate course progress if newly completed
        if ($isCompleted && !$wasCompleted) {
            $this->recalculateCourseProgress($userId, $courseId);
        }

        return [
            'completion_percentage' => min(100, $completionPct),
            'is_completed'          => $isCompleted,
        ];
    }
}
