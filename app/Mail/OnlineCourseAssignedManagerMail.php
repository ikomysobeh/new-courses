<?php

namespace App\Mail;

use App\Models\CourseOnline;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnlineCourseAssignedManagerMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly CourseOnline $course,
        public readonly User $assignedUser,
        public readonly User $manager,
        public readonly User $assignedBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Exclusive Team Course Assignment - Manager Notification',
        );
    }

    public function content(): Content
    {
        $teamMembers = collect([$this->assignedUser->loadMissing('department')]);

        [$hasDeadline, $deadlineDate, $deadlineStatus, $daysUntilDeadline, $urgencyMessage] =
            $this->resolveDeadlineMeta();

        return new Content(
            view: 'emails.online-courses.assigned-manager',
            with: [
                'course'       => $this->course,
                'assignedUser' => $this->assignedUser,
                'manager'      => $this->manager,
                'assignedBy'   => $this->assignedBy,
                'teamMembers' => $teamMembers,
                'hasDeadline' => $hasDeadline,
                'deadlineDate' => $deadlineDate,
                'deadlineStatus' => $deadlineStatus,
                'daysUntilDeadline' => $daysUntilDeadline,
                'urgencyMessage' => $urgencyMessage,
                'deadlineType' => 'flexible',
            ],
        );
    }

    private function resolveDeadlineMeta(): array
    {
        if (! $this->course->deadline) {
            return [false, null, 'normal', null, null];
        }

        $deadline = Carbon::parse($this->course->deadline);
        $now = now();
        $daysUntilDeadline = $now->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);

        $deadlineStatus = match (true) {
            $daysUntilDeadline < 0 => 'overdue',
            $daysUntilDeadline === 0 => 'due_today',
            $daysUntilDeadline <= 2 => 'due_soon',
            $daysUntilDeadline <= 7 => 'due_this_week',
            default => 'normal',
        };

        $urgencyMessage = match ($deadlineStatus) {
            'overdue' => 'Immediate action is required. The course deadline has already passed.',
            'due_today' => 'Immediate action is required. The course is due today.',
            'due_soon' => 'Please prioritize follow-up. The course is due very soon.',
            'due_this_week' => 'Please ensure completion progress is on track this week.',
            default => 'Please monitor progress and support timely completion.',
        };

        return [
            true,
            $deadline->format('M d, Y'),
            $deadlineStatus,
            $daysUntilDeadline,
            $urgencyMessage,
        ];
    }
}
