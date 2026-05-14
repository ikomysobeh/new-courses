<?php

namespace App\Mail;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class QuizAssignedUserMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public readonly mixed $course;
    public readonly string $courseType;
    public readonly bool $hasDeadline;
    public readonly mixed $deadline;
    public readonly string $deadlineFormatted;
    public readonly array $deadlineStatus;
    public readonly string $timeUntilDeadline;
    public readonly bool $enforceDeadline;
    public readonly ?int $timeLimitMinutes;
    public readonly string $quizLink;

    public function __construct(
        public readonly Quiz $quiz,
        public readonly User $user,
    ) {
        // Resolve course (regular or online)
        if ($quiz->course_id && $quiz->course) {
            $this->course     = $quiz->course;
            $this->courseType = 'regular';
        } elseif ($quiz->course_online_id && $quiz->courseOnline) {
            $this->course     = $quiz->courseOnline;
            $this->courseType = 'online';
        } else {
            $this->course     = (object) ['name' => 'N/A'];
            $this->courseType = 'regular';
        }

        // Deadline
        $this->hasDeadline       = (bool) $quiz->deadline;
        $this->deadline          = $quiz->deadline;
        $this->enforceDeadline   = false; // quizzes don't have a strict enforce flag; adjust if needed
        $this->timeLimitMinutes  = $quiz->time_limit_minutes;
        $this->quizLink          = config('app.url') . '/quizzes/' . $quiz->id;

        if ($quiz->deadline) {
            $now      = Carbon::now();
            $deadline = Carbon::parse($quiz->deadline);
            $diffDays = $now->diffInDays($deadline, false);
            $diffHours = $now->diffInHours($deadline, false);

            $this->deadlineFormatted = $deadline->format('l, F j, Y \a\t g:i A');

            if ($deadline->isPast()) {
                $this->timeUntilDeadline = 'Deadline has passed';
                $this->deadlineStatus    = ['status' => 'expired', 'message' => 'The deadline has passed.'];
            } elseif ($diffHours < 24) {
                $this->timeUntilDeadline = "Less than {$diffHours} hour(s) remaining";
                $this->deadlineStatus    = ['status' => 'urgent', 'message' => "Only {$diffHours} hour(s) left!"];
            } elseif ($diffDays <= 3) {
                $this->timeUntilDeadline = "{$diffDays} day(s) remaining";
                $this->deadlineStatus    = ['status' => 'soon', 'message' => "{$diffDays} day(s) remaining."];
            } else {
                $this->timeUntilDeadline = "{$diffDays} day(s) remaining";
                $this->deadlineStatus    = ['status' => 'normal', 'message' => "{$diffDays} day(s) remaining."];
            }
        } else {
            $this->deadlineFormatted = '';
            $this->timeUntilDeadline = '';
            $this->deadlineStatus    = ['status' => 'normal', 'message' => ''];
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Quiz Available: ' . $this->quiz->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quiz.assigned-user',
        );
    }
}
