<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserDashboardService
{
    /**
     * Build the personalized dashboard payload for a user.
     */
    public function build(User $user): array
    {
        $userId = (int) $user->id;

        $offline = $this->offlineCourses($userId);
        $online  = $this->onlineCourses($userId);

        return [
            'user' => [
                'id'     => $user->id,
                'name'   => $user->name,
                'avatar' => null,
            ],
            'summary'           => $this->summary($userId, $offline, $online),
            'courses'           => [
                'offline' => $offline,
                'online'  => $online,
            ],
            'recent_attendance' => $this->recentAttendance($userId),
        ];
    }

    /**
     * Traditional / live courses the user is registered in.
     */
    private function offlineCourses(int $userId): array
    {
        return DB::table('course_registrations as cr')
            ->join('courses as c', 'c.id', '=', 'cr.course_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.created_by')
            ->whereNull('c.deleted_at')
            ->where('cr.user_id', $userId)
            ->orderByRaw("FIELD(cr.status, 'in_progress', 'pending', 'completed')")
            ->get([
                'c.id', 'c.name', 'c.image_path', 'c.duration',
                'cr.status', 'u.name as instructor',
            ])
            ->map(fn ($r) => [
                'id'           => $r->id,
                'title'        => $r->name,
                'instructor'   => $r->instructor,
                'progress'     => $this->statusToPercent($r->status),
                'status'       => $r->status,
                'duration'     => $this->formatMinutes((int) ($r->duration ?? 0)),
                'image'        => $this->imageUrl($r->image_path),
                'continue_url' => '/user/courses/' . $r->id,
            ])
            ->all();
    }

    /**
     * Online courses the user is assigned to, with real progress.
     */
    private function onlineCourses(int $userId): array
    {
        return DB::table('course_online_assignments as coa')
            ->join('course_onlines as co', 'co.id', '=', 'coa.course_online_id')
            ->leftJoin('users as u', 'u.id', '=', 'co.created_by')
            ->leftJoin('user_course_progress as ucp', function ($join) use ($userId) {
                $join->on('ucp.course_online_id', '=', 'co.id')
                    ->where('ucp.user_id', '=', $userId);
            })
            ->whereNull('co.deleted_at')
            ->where('coa.user_id', $userId)
            ->orderByDesc('coa.assigned_at')
            ->get([
                'co.id', 'co.name', 'co.image_path', 'co.estimated_duration',
                'u.name as instructor',
                'ucp.progress_percentage', 'ucp.status',
            ])
            ->map(fn ($r) => [
                'id'           => $r->id,
                'title'        => $r->name,
                'instructor'   => $r->instructor,
                'progress'     => (float) round((float) ($r->progress_percentage ?? 0), 1),
                'status'       => $r->status ?? 'not_started',
                'duration'     => $this->formatMinutes((int) ($r->estimated_duration ?? 0)),
                'image'        => $this->imageUrl($r->image_path),
                'continue_url' => '/user/online-courses/' . $r->id,
            ])
            ->all();
    }

    /**
     * High-level counts for the welcome banner.
     */
    private function summary(int $userId, array $offline, array $online): array
    {
        $inProgress = collect($offline)->where('status', 'in_progress')->count()
            + collect($online)->where('status', 'in_progress')->count();

        $completed = collect($offline)->where('status', 'completed')->count()
            + collect($online)->where('status', 'completed')->count();

        // Upcoming = active course sessions in the future for courses the user is registered in.
        $upcoming = DB::table('course_availabilities as ca')
            ->join('course_registrations as cr', function ($join) use ($userId) {
                $join->on('cr.course_id', '=', 'ca.course_id')
                    ->where('cr.user_id', '=', $userId);
            })
            ->where('ca.status', 'active')
            ->where('ca.start_date', '>=', now())
            ->count();

        return [
            'in_progress_count' => $inProgress,
            'upcoming_count'    => $upcoming,
            'completed_count'   => $completed,
        ];
    }

    /**
     * Most recent clocking (attendance) records.
     */
    private function recentAttendance(int $userId): array
    {
        return DB::table('clockings as cl')
            ->leftJoin('courses as c', 'c.id', '=', 'cl.course_id')
            ->where('cl.user_id', $userId)
            ->orderByDesc('cl.clock_in')
            ->limit(8)
            ->get(['cl.id', 'cl.clock_in', 'cl.clock_out', 'c.name as course'])
            ->map(fn ($r) => [
                'id'     => $r->id,
                'date'   => $r->clock_in,
                'course' => $r->course ?? 'General',
                'status' => $r->clock_out ? 'completed' : 'in_progress',
            ])
            ->all();
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function statusToPercent(?string $status): int
    {
        return match ($status) {
            'completed'   => 100,
            'in_progress' => 50,
            default       => 0, // pending / not_started
        };
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '—';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $h > 0 ? sprintf('%dh %02dm', $h, $m) : sprintf('%dm', $m);
    }

    private function imageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
