<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseAssignedUserMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Course $course,
        public readonly User $assignedUser,
        public readonly User $assignedBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You Have Been Assigned a Course: ' . $this->course->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.courses.assigned-user',
        );
    }
}
