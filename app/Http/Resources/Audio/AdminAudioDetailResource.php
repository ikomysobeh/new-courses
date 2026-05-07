<?php

namespace App\Http\Resources\Audio;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminAudioDetailResource extends AudioResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);

        $fileSizeBytes = null;
        $mimeType = null;

        if ($this->local_path && Storage::disk('local')->exists($this->local_path)) {
            $fileSizeBytes = Storage::disk('local')->size($this->local_path);
            $mimeType = mime_content_type(Storage::disk('local')->path($this->local_path)) ?: null;
        }

        // Build a lookup: user_id => AudioProgress row
        $progressByUser = [];
        if ($this->relationLoaded('progress')) {
            foreach ($this->progress as $p) {
                $progressByUser[$p->user_id] = $p;
            }
        }

        $duration = (int) ($this->duration ?? 0);

        $listeners = [];
        if ($this->relationLoaded('assignments')) {
            foreach ($this->assignments as $assignment) {
                $user = $assignment->user;
                if (! $user) {
                    continue;
                }

                $p = $progressByUser[$user->id] ?? null;

                $completionPct  = $p ? (float) $p->completion_percentage : 0.0;
                $currentTime    = $p ? (float) $p->current_time : 0.0;
                $isCompleted    = $p && $p->is_completed;
                $listenedSecs   = $p ? (int) $p->total_listened_time : 0;
                $lastAccessedAt = $p ? $p->last_accessed_at : null;

                if ($isCompleted) {
                    $status = 'completed';
                } elseif ($p && $currentTime > 0) {
                    $status = 'in_progress';
                } else {
                    $status = 'not_started';
                }

                $listeners[] = [
                    'user'                    => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                    ],
                    'progress_percentage'     => round($completionPct, 1),
                    'current_position_seconds'=> (int) $currentTime,
                    'total_duration_seconds'  => $duration,
                    'status'                  => $status,
                    'listening_time_minutes'  => (int) round($listenedSecs / 60),
                    'last_accessed_at'        => $lastAccessedAt,
                    'assigned_at'             => $assignment->assigned_at,
                ];
            }
        }

        // Aggregate stats
        $totalListeners   = count($listeners);
        $completedCount   = count(array_filter($listeners, fn ($l) => $l['status'] === 'completed'));
        $completionRate   = $totalListeners > 0 ? round(($completedCount / $totalListeners) * 100, 1) : 0.0;
        $avgProgress      = $totalListeners > 0
            ? round(array_sum(array_column($listeners, 'progress_percentage')) / $totalListeners, 1)
            : 0.0;
        $totalSecsListened = array_sum(array_map(
            fn ($l) => $l['listening_time_minutes'] * 60,
            $listeners
        ));
        $totalHoursListened = round($totalSecsListened / 3600, 2);

        return array_merge($base, [
            'stream_url'       => $this->local_path ? route('admin.audio.stream', $this->id) : null,
            'file_size_bytes'  => $fileSizeBytes,
            'file_size'        => $fileSizeBytes !== null ? $this->formatBytes($fileSizeBytes) : null,
            'mime_type'        => $mimeType,
            'stats'            => [
                'total_listeners'     => $totalListeners,
                'completion_rate'     => $completionRate,
                'average_progress'    => $avgProgress,
                'total_hours_listened'=> $totalHoursListened,
            ],
            'listeners'        => $listeners,
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
