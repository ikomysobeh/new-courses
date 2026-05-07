<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\CourseAssignment;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CourseAssignedManagerMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Course $course,
        public readonly User $assignedUser,
        public readonly User $manager,
        public readonly User $assignedBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A Course Has Been Assigned to Your Team Member',
        );
    }

    public function content(): Content
    {
        $course = $this->course->loadMissing('availabilities');
        $assignedUsers = collect([$this->assignedUser->loadMissing('department')]);
        $assignmentDate = CourseAssignment::query()
            ->where('course_id', $this->course->id)
            ->where('user_id', $this->assignedUser->id)
            ->where('assigned_by', $this->assignedBy->id)
            ->latest('assigned_at')
            ->value('assigned_at');

        return new Content(
            view: 'emails.courses.assigned-manager',
            with: [
                'course' => $course,
                'manager' => $this->manager,
                'assignedBy' => $this->assignedBy,
                'assignedUser' => $this->assignedUser,
                'assignedUsers' => $assignedUsers,
                'userCount' => $assignedUsers->count(),
                'assignmentDate' => $assignmentDate
                    ? Carbon::parse($assignmentDate)->format('F j, Y')
                    : now()->format('F j, Y'),
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
