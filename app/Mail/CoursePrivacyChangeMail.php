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

class CoursePrivacyChangeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Course $course,
        public readonly User $recipient,
        public readonly ?string $loginLink = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Course Now Available: ' . $this->course->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.courses.privacy-change',
            with: [
                'course' => $this->course,
                'user' => $this->recipient,
                'recipient' => $this->recipient,
                'loginLink' => $this->loginLink,
            ],
        );
    }
}
