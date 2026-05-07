<?php

namespace App\Services\Course;

use App\Models\Clocking;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ClockingService
{
    public function getAdminAttendanceCards(): array
    {
        $totalRecords = Clocking::query()->count();
        $activeSessions = Clocking::query()->whereNull('clock_out')->count();
        $totalHours = round(((int) Clocking::query()->sum('duration_in_minutes')) / 60, 2);

        return [
            [
                'key' => 'total_clocking_records',
                'title' => 'Total Clocking Records',
                'value' => $totalRecords,
            ],
            [
                'key' => 'active_sessions',
                'title' => 'Active Sessions',
                'value' => $activeSessions,
            ],
            [
                'key' => 'total_hours_logged',
                'title' => 'Total Hours Logged',
                'value' => $totalHours,
            ],
        ];
    }

    public function clockIn(User $user, ?int $courseId): Clocking
    {
        $openSession = $this->findOpenSession($user);

        if ($openSession !== null) {
            throw ValidationException::withMessages([
                'clock_in' => ['You already have an open clocking session. Please clock out first.'],
            ]);
        }

        return Clocking::query()->create([
            'user_id'   => $user->id,
            'course_id' => $courseId,
            'clock_in'  => now(),
        ]);
    }

    public function clockOut(User $user, ?int $rating, ?string $comment): Clocking
    {
        $session = $this->findOpenSession($user);

        if ($session === null) {
            throw new ModelNotFoundException('No open clocking session found.');
        }

        $clockOut          = now();
        $durationInMinutes = (int) $session->clock_in->diffInMinutes($clockOut);

        $session->update([
            'clock_out'            => $clockOut,
            'duration_in_minutes'  => $durationInMinutes,
            'rating'               => $rating,
            'comment'              => $comment,
        ]);

        return $session->fresh()->load('course');
    }

    public function getActiveSession(User $user): ?Clocking
    {
        return Clocking::query()
            ->where('user_id', $user->id)
            ->whereNull('clock_out')
            ->with('course')
            ->first();
    }

    public function getUserHistory(User $user): LengthAwarePaginator
    {
        return Clocking::query()
            ->where('user_id', $user->id)
            ->with('course')
            ->orderByDesc('clock_in')
            ->paginate(15);
    }

    public function updateAttendance(int $clockingId, array $data): Clocking
    {
        $clocking = Clocking::query()->findOrFail($clockingId);

        $payload = [];

        if (array_key_exists('clock_in', $data)) {
            $payload['clock_in'] = $data['clock_in'];
        }

        if (array_key_exists('clock_out', $data)) {
            $payload['clock_out'] = $data['clock_out'];
        }

        if (array_key_exists('rating', $data)) {
            $payload['rating'] = $data['rating'];
        }

        if (array_key_exists('comment', $data)) {
            $payload['comment'] = $data['comment'];
        }

        // Recalculate duration if both times are present after merge
        $effectiveClockIn  = $payload['clock_in']  ?? $clocking->clock_in;
        $effectiveClockOut = $payload['clock_out'] ?? $clocking->clock_out;

        if ($effectiveClockIn && $effectiveClockOut) {
            $payload['duration_in_minutes'] = (int) \Carbon\Carbon::parse($effectiveClockIn)
                ->diffInMinutes(\Carbon\Carbon::parse($effectiveClockOut));
        }

        $clocking->update($payload);

        return $clocking->fresh()->load('course');
    }

    public function deleteAttendance(int $clockingId): void
    {
        $clocking = Clocking::query()->findOrFail($clockingId);
        $clocking->delete();
    }

    public function getAllForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = Clocking::query()
            ->with(['user', 'course'])
            ->orderByDesc('clock_in');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        return $query->paginate(15);
    }

    private function findOpenSession(User $user): ?Clocking
    {
        return Clocking::query()
            ->where('user_id', $user->id)
            ->whereNull('clock_out')
            ->first();
    }
}
