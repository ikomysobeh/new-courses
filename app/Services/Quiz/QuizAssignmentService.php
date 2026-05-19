<?php

namespace App\Services\Quiz;

use App\Mail\QuizAssignedUserMail;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class QuizAssignmentService
{
    public function getAssignmentsList(array $filters = []): Collection
    {
        $query = QuizAssignment::query()->with(['user', 'quiz', 'assigner']);

        if (!empty($filters['quiz_id'])) {
            $query->where('quiz_id', $filters['quiz_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['notification_sent'])) {
            $query->where('notification_sent', $filters['notification_sent']);
        }

        return $query->orderByDesc('id')->get();
    }

    public function assignQuizToUsers(int $quizId, array $userIds, int $assignedBy): array
    {
        return DB::transaction(function () use ($quizId, $userIds, $assignedBy) {
            $existing = QuizAssignment::query()
                ->where('quiz_id', $quizId)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->all();

            $newUserIds = array_values(array_diff($userIds, $existing));

            if (!empty($newUserIds)) {
                $quiz = Quiz::query()
                    ->with(['course', 'courseOnline'])
                    ->findOrFail($quizId);

                foreach ($newUserIds as $userId) {
                    $assignment = QuizAssignment::query()->create([
                        'user_id'     => $userId,
                        'quiz_id'     => $quizId,
                        'assigned_by' => $assignedBy,
                        'assigned_at' => Carbon::now(),
                    ]);

                    try {
                        $user = User::query()->findOrFail($userId);
                        Mail::to($user->email)->queue(new QuizAssignedUserMail($quiz, $user));
                        $this->markNotificationSent($assignment->id);
                    } catch (\Throwable $e) {
                        QuizAssignment::query()->where('id', $assignment->id)->update(['notification_sent' => false]);
                        report($e);
                    }
                }
            }

            return $newUserIds;
        });
    }

    public function removeAssignment(int $assignmentId): void
    {
        $assignment = QuizAssignment::query()->findOrFail($assignmentId);
        $assignment->delete();
    }

    public function markNotificationSent(int $assignmentId): void
    {
        QuizAssignment::query()->where('id', $assignmentId)->update(['notification_sent' => true]);
    }

    public function getAdminAssignmentCards(): array
    {
        $total    = QuizAssignment::query()->count();
        $notified = QuizAssignment::query()->where('notification_sent', true)->count();
        $pending  = QuizAssignment::query()->where('notification_sent', false)->count();

        return [
            ['key' => 'total_assignments',     'title' => 'Total Assignments',     'value' => $total],
            ['key' => 'notified_assignments',  'title' => 'Notified Assignments',  'value' => $notified],
            ['key' => 'pending_notifications', 'title' => 'Pending Notifications', 'value' => $pending],
        ];
    }
}
