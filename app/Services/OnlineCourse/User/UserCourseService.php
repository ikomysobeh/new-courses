<?php

namespace App\Services\OnlineCourse\User;

use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\ModuleContent;
use App\Models\QuizAttempt;
use App\Models\UserContentProgress;
use App\Models\UserCourseProgress;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\URL;

class UserCourseService
{
    public function getUserCourses(int $userId, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        $query = CourseOnlineAssignment::where('user_id', $userId)
            ->with([
                'course' => function ($q) {
                    $q->with(['modules']);
                },
            ]);

        $paginator = $query->paginate($perPage);

        // Attach progress to each course
        $courseIds = $paginator->getCollection()->pluck('course_online_id');
        $progressMap = UserCourseProgress::where('user_id', $userId)
            ->whereIn('course_online_id', $courseIds)
            ->get()
            ->keyBy('course_online_id');

        // Attach total_content_items count
        $contentCountMap = ModuleContent::whereHas('module', function ($q) use ($courseIds) {
            $q->whereIn('course_online_id', $courseIds);
        })
        ->where('is_required', true)
        ->selectRaw('course_modules.course_online_id, COUNT(*) as total')
        ->join('course_modules', 'module_contents.module_id', '=', 'course_modules.id')
        ->groupBy('course_modules.course_online_id')
        ->pluck('total', 'course_online_id');

        $paginator->getCollection()->transform(function ($assignment) use ($progressMap, $contentCountMap, $filters) {
            $course    = $assignment->course;
            $progress  = $progressMap->get($assignment->course_online_id);

            // Apply status filter
            if (!empty($filters['status'])) {
                $currentStatus = $progress?->status ?? 'not_started';
                if ($currentStatus !== $filters['status']) {
                    return null;
                }
            }

            // Apply search filter
            if (!empty($filters['search'])) {
                if (!str_contains(strtolower($course->name ?? ''), strtolower($filters['search']))) {
                    return null;
                }
            }

            $course->userProgress = $progress ? collect([$progress]) : collect();
            $course->assignmentForUser = collect([$assignment]);
            $course->total_content_items = $contentCountMap->get($assignment->course_online_id, 0);
            $course->modules_count = $course->modules->count();

            return $course;
        });

        // Remove nulls from filtered items
        $paginator->setCollection($paginator->getCollection()->filter()->values());

        return $paginator;
    }

    public function getCourseDetail(int $userId, int $courseOnlineId): array
    {
        // Verify assignment
        $assigned = CourseOnlineAssignment::where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->exists();

        if (!$assigned) {
            abort(403, 'Not assigned to this course.');
        }

        $course = CourseOnline::with([
            'modules' => function ($q) {
                $q->orderBy('order_number')->with([
                    'contents' => function ($q2) {
                        $q2->orderBy('order_number');
                    },
                    'quiz',
                ]);
            },
        ])->findOrFail($courseOnlineId);

        // Load ALL content progress in one query
        $allContentIds = $course->modules->flatMap(fn ($m) => $m->contents->pluck('id'));

        $progressMap = UserContentProgress::where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->whereIn('content_id', $allContentIds)
            ->get()
            ->keyBy('content_id');

        // Load ALL quiz attempts in one query
        $quizIds = $course->modules->pluck('quiz.id')->filter();
        $quizAttemptsMap = QuizAttempt::where('user_id', $userId)
            ->whereIn('quiz_id', $quizIds)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('quiz_id');

        $progress = UserCourseProgress::where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->first();

        $modules = [];
        $prevModuleCompleted = true; // First module always unlocked

        foreach ($course->modules as $index => $module) {
            $isUnlocked = $index === 0 || $prevModuleCompleted;

            // Determine if module is completed
            $moduleCompleted = $this->isModuleCompletedFromMap(
                $module, $userId, $progressMap, $quizAttemptsMap
            );

            // Quiz status
            $quizStatus = null;
            if ($module->has_quiz && $module->quiz) {
                $attempts = $quizAttemptsMap->get($module->quiz->id, collect());
                if ($attempts->isEmpty()) {
                    $quizStatus = 'not_attempted';
                } elseif ($attempts->contains('passed', true)) {
                    $quizStatus = 'passed';
                } else {
                    $quizStatus = 'failed';
                }
            }

            // Build content array
            $contentItems = [];
            foreach ($module->contents as $ci => $contentItem) {
                $contentIsUnlocked = $isUnlocked;
                $contentProgress   = $progressMap->get($contentItem->id);

                $contentItems[] = [
                    'item'        => $contentItem,
                    'is_unlocked' => $contentIsUnlocked,
                    'progress'    => $contentProgress,
                ];
            }

            $modules[] = [
                'module'       => $module,
                'is_unlocked'  => $isUnlocked,
                'is_completed' => $moduleCompleted,
                'quiz_status'  => $quizStatus,
                'content'      => $contentItems,
            ];

            $prevModuleCompleted = $moduleCompleted;
        }

        return [
            'course'   => $course,
            'modules'  => $modules,
            'progress' => $progress,
        ];
    }

    public function getContentView(int $userId, int $courseOnlineId, int $contentId): array
    {
        // Verify assignment
        $assigned = CourseOnlineAssignment::where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->exists();

        if (!$assigned) {
            abort(403, 'Not assigned to this course.');
        }

        // Verify content belongs to this course
        $content = ModuleContent::whereHas('module', function ($q) use ($courseOnlineId) {
            $q->where('course_online_id', $courseOnlineId);
        })->with(['module', 'pdf', 'video'])->find($contentId);

        if (!$content) {
            abort(404, 'Content not found in this course.');
        }

        if (!$content->is_active) {
            abort(403, 'Content is not active.');
        }

        // Verify content is unlocked (sequential module logic)
        $course = CourseOnline::with([
            'modules' => function ($q) {
                $q->orderBy('order_number')->with([
                    'contents' => fn ($q2) => $q2->orderBy('order_number'),
                    'quiz',
                ]);
            },
        ])->find($courseOnlineId);

        $allContentIds = $course->modules->flatMap(fn ($m) => $m->contents->pluck('id'));
        $progressMap   = UserContentProgress::where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->whereIn('content_id', $allContentIds)
            ->get()
            ->keyBy('content_id');

        $quizIds = $course->modules->pluck('quiz.id')->filter();
        $quizAttemptsMap = QuizAttempt::where('user_id', $userId)
            ->whereIn('quiz_id', $quizIds)
            ->get()
            ->groupBy('quiz_id');

        $contentUnlocked = false;
        $prevModuleCompleted = true;

        foreach ($course->modules as $index => $module) {
            $isUnlocked = $index === 0 || $prevModuleCompleted;

            if ($isUnlocked && $module->contents->contains('id', $contentId)) {
                $contentUnlocked = true;
            }

            $prevModuleCompleted = $this->isModuleCompletedFromMap(
                $module, $userId, $progressMap, $quizAttemptsMap
            );
        }

        if (!$contentUnlocked) {
            abort(403, 'Content is locked.');
        }

        // Generate signed URL
        $routeName = $content->content_type === 'pdf' ? 'media.pdf' : 'media.video';
        $mediaUrl  = URL::temporarySignedRoute(
            $routeName,
            now()->addHours(4),
            ['content_id' => $contentId]
        );

        $userProgress = $progressMap->get($contentId);

        // Find next/prev content in the same module
        $siblingContents = $content->module->contents->sortBy('order_number')->values();
        $currentIndex    = $siblingContents->search(fn ($c) => $c->id === $contentId);

        $nextContent = $currentIndex !== false && $currentIndex < $siblingContents->count() - 1
            ? $siblingContents[$currentIndex + 1]
            : null;

        $prevContent = $currentIndex !== false && $currentIndex > 0
            ? $siblingContents[$currentIndex - 1]
            : null;

        return [
            'content'         => $content,
            'media_url'       => $mediaUrl,
            'pdf_total_pages' => $content->content_type === 'pdf' ? $content->pdf?->pdf_page_count : null,
            'progress'        => $userProgress,
            'next_content'    => $nextContent,
            'prev_content'    => $prevContent,
        ];
    }

    public function getResumePosition(int $userId, int $contentId): array
    {
        $progress = UserContentProgress::where('user_id', $userId)
            ->where('content_id', $contentId)
            ->first();

        if (!$progress) {
            return [
                'playback_position'     => 0,
                'completion_percentage' => 0,
                'is_completed'          => false,
                'last_accessed_at'      => null,
            ];
        }

        return [
            'playback_position'     => (float) $progress->playback_position,
            'completion_percentage' => (float) $progress->completion_percentage,
            'is_completed'          => (bool) $progress->is_completed,
            'last_accessed_at'      => $progress->last_accessed_at,
        ];
    }

    private function isModuleCompletedFromMap(
        CourseModule $module,
        int $userId,
        $progressMap,
        $quizAttemptsMap
    ): bool {
        $requiredContentIds = $module->contents
            ->where('is_required', true)
            ->pluck('id');

        if ($requiredContentIds->isEmpty()) {
            $contentCompleted = true;
        } else {
            $completedCount = $requiredContentIds->filter(function ($id) use ($progressMap) {
                return $progressMap->get($id)?->is_completed === true;
            })->count();

            $contentCompleted = $completedCount >= $requiredContentIds->count();
        }

        if (!$contentCompleted) {
            return false;
        }

        if ($module->has_quiz && $module->quiz_required && $module->quiz) {
            $attempts = $quizAttemptsMap->get($module->quiz->id, collect());
            return $attempts->contains('passed', true);
        }

        return true;
    }
}
