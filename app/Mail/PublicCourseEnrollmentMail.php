<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

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
        $course = $this->course->load(['availabilities' => fn ($q) => $q->active()]);
        $assignedUsers = collect([$this->enrolledUser->loadMissing('department')]);

        return new Content(
            view: 'emails.courses.enrollment-manager',
            with: [
                'course' => $course,
                'manager' => $this->manager,
                'enrolledUser' => $this->enrolledUser,
                'assignedUsers' => $assignedUsers,
                'userCount' => $assignedUsers->count(),
                'assignmentDate' => now()->format('F j, Y'),
                'availabilities' => $this->formatAvailabilities($course),
            ],
        );
    }

    private function formatAvailabilities(Course $course): Collection
    {
        return $course->availabilities
            ->sortBy('start_date')
            ->map(function ($availability) {
                $days = collect(explode(',', (string) ($availability->days_of_week ?? '')))
                    ->map(fn ($day) => trim($day))
                    ->filter()
                    ->map(fn ($day) => ucfirst(strtolower($day)))
                    ->values();

                $timeShifts = collect([
                    $availability->session_time_shift_1,
                    $availability->session_time_shift_2,
                    $availability->session_time_shift_3,
                ])->filter();

                $formattedSessionTime = $timeShifts
                    ->map(fn ($time) => Carbon::parse($time)->format('g:i A'))
                    ->implode(' | ');

                $sessionDuration = $availability->session_duration_minutes;
                $formattedSessionDuration = $sessionDuration
                    ? ($sessionDuration >= 60
                        ? number_format($sessionDuration / 60, 1) . ' hours'
                        : $sessionDuration . ' minutes')
                    : null;

                $startDate = $availability->start_date;
                $endDate = $availability->end_date;

                return [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'capacity' => (int) $availability->capacity,
                    'sessions' => (int) $availability->sessions,
                    'available_spots' => (int) $availability->sessions,
                    'notes' => $availability->notes,
                    'duration_weeks' => $availability->duration_weeks,
                    'formatted_days' => $days->isNotEmpty() ? $days->implode(', ') : 'TBD',
                    'formatted_session_time' => $formattedSessionTime !== '' ? $formattedSessionTime : null,
                    'formatted_session_duration' => $formattedSessionDuration,
                    'formatted_date_range' => $startDate && $endDate
                        ? Carbon::parse($startDate)->format('M j, Y') . ' - ' . Carbon::parse($endDate)->format('M j, Y')
                        : 'TBD',
                ];
            })
            ->values();
    }
}
