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

class PublicCourseEnrollmentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Course $course,
        public readonly User $enrolledUser,
        public readonly User $manager,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Team Member Enrolled in a Course',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.courses.enrollment-manager',
        );
    }
}
