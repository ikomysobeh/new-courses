<?php

namespace App\Services\Support;

use App\Models\EmployeeFeedback;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeedbackService
{
    public function submitFeedback(int $userId, array $data): EmployeeFeedback
    {
        $feedback = EmployeeFeedback::create([
            'user_id'     => $userId,
            'type'        => $data['type'],
            'title'       => $data['title'],
            'description' => $data['description'],
            'status'      => 'pending',
        ]);

        $user = User::find($userId);

        ActivityService::log(
            "Feedback submitted: {$feedback->title}",
            ActivityService::ACTION_FEEDBACK_SUBMITTED,
            $user,
            $feedback
        );

        return $feedback;
    }

    public function getAllForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = EmployeeFeedback::with('user.department')->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate(15);
    }

    public function getById(int $id): EmployeeFeedback
    {
        return EmployeeFeedback::with('user.department')->findOrFail($id);
    }

    public function getForUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = EmployeeFeedback::where('user_id', $userId)->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate(15);
    }

    public function respond(int $id, string $response, string $status): EmployeeFeedback
    {
        $feedback = EmployeeFeedback::findOrFail($id);

        $feedback->update([
            'admin_response' => $response,
            'status'         => $status,
        ]);

        ActivityService::log(
            "Feedback responded: {$feedback->title}",
            ActivityService::ACTION_FEEDBACK_RESPONDED,
            null,
            $feedback,
            ['status' => $status]
        );

        return $feedback->load('user.department');
    }

    public function updateStatus(int $id, string $status): EmployeeFeedback
    {
        $feedback = EmployeeFeedback::findOrFail($id);

        $oldStatus = $feedback->status;
        $feedback->update(['status' => $status]);

        ActivityService::log(
            "Feedback status changed: {$feedback->title}",
            ActivityService::ACTION_FEEDBACK_STATUS_CHANGED,
            null,
            $feedback,
            ['old_status' => $oldStatus, 'new_status' => $status]
        );

        return $feedback->load('user.department');
    }
}
