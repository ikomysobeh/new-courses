<?php

namespace App\Mail;

use App\Models\CourseOnline;
use App\Models\User;
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
            subject: 'Team Member Online Course Assignment Notification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.online-courses.assigned-manager',
            with: [
                'course'       => $this->course,
                'assignedUser' => $this->assignedUser,
                'manager'      => $this->manager,
                'assignedBy'   => $this->assignedBy,
            ],
        );
    }
}
