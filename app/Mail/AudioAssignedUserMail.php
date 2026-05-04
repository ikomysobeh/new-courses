<?php

namespace App\Mail;

use App\Models\Audio;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AudioAssignedUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly User $assignedBy,
        public readonly Audio $audio,
        public readonly ?string $loginLink = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Audio Assignment',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.audio.assigned-user',
            with: [
                'user' => $this->user,
                'assignedBy' => $this->assignedBy,
                'audioName' => $this->audio->name,
                'audioDescription' => $this->audio->description,
                'audioDuration' => $this->formatDuration($this->audio->duration),
                'loginLink' => $this->loginLink,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function formatDuration(?int $seconds): ?string
    {
        if (! $seconds || $seconds <= 0) {
            return null;
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0 && $remainingSeconds > 0) {
            return sprintf('%d min %d sec', $minutes, $remainingSeconds);
        }

        if ($minutes > 0) {
            return sprintf('%d min', $minutes);
        }

        return sprintf('%d sec', $remainingSeconds);
    }
}
