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

class OnlineCourseAssignedUserMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly CourseOnline $course,
        public readonly User $assignedUser,
        public readonly User $assignedBy,
        public readonly ?string $loginLink = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Exclusive Online Course Assignment - Professional Development',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.online-courses.assigned-user',
        );
    }
}
