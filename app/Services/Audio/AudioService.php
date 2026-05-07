<?php

namespace App\Services\Audio;

use App\Events\AudioAssigned;
use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\AudioCategory;
use App\Models\AudioProgress;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AudioService
{
    public const AUTO_COMPLETE_THRESHOLD = 95.00;

    public function getCategoryCards(): array
    {
        $totalCategories = AudioCategory::query()->count();
        $totalAudios = Audio::query()->count();
        $activeCategories = AudioCategory::query()->has('audios')->count();

        return [
            [
                'key' => 'total_categories',
                'title' => 'Total Categories',
                'value' => $totalCategories,
            ],
            [
                'key' => 'total_audios',
                'title' => 'Total Audios',
                'value' => $totalAudios,
            ],
            [
                'key' => 'active_categories',
                'title' => 'Categories With Audio',
                'value' => $activeCategories,
            ],
        ];
    }

    public function getAdminAudioCards(): array
    {
        $totalAudios = Audio::query()->count();
        $totalCategories = AudioCategory::query()->count();
        $totalAssignments = AudioAssignment::query()->count();
        $totalDurationSeconds = (int) Audio::query()->sum('duration');

        return [
            [
                'key' => 'total_audios',
                'title' => 'Total Audios',
                'value' => $totalAudios,
            ],
            [
                'key' => 'total_categories',
                'title' => 'Total Categories',
                'value' => $totalCategories,
            ],
            [
                'key' => 'total_assignments',
                'title' => 'Total Assignments',
                'value' => $totalAssignments,
            ],
            [
                'key' => 'total_duration_seconds',
                'title' => 'Total Duration (sec)',
                'value' => $totalDurationSeconds,
            ],
        ];
    }

    public function getAdminAudioAssignmentCards(): array
    {
        return [
            [
                'key' => 'total_audio_assignments',
                'title' => 'Total Audio Assignments',
                'value' => AudioAssignment::query()->count(),
            ],
            [
                'key' => 'assigned_users',
                'title' => 'Users With Audio Assignments',
                'value' => AudioAssignment::query()->distinct('user_id')->count('user_id'),
            ],
            [
                'key' => 'assigned_audios',
                'title' => 'Audios With Assignments',
                'value' => AudioAssignment::query()->distinct('audio_id')->count('audio_id'),
            ],
        ];
    }

    public function getUserAudioCards(User $user): array
    {
        $totalAssigned = AudioAssignment::query()
            ->where('user_id', $user->id)
            ->count();

        $completed = AudioProgress::query()
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->count();

        $inProgress = AudioProgress::query()
            ->where('user_id', $user->id)
            ->where('is_completed', false)
            ->where('completion_percentage', '>', 0)
            ->count();

        $remaining = max($totalAssigned - $completed, 0);

        return [
            [
                'key' => 'assigned_audios',
                'title' => 'Assigned Audios',
                'value' => $totalAssigned,
            ],
            [
                'key' => 'completed_audios',
                'title' => 'Completed Audios',
                'value' => $completed,
            ],
            [
                'key' => 'in_progress_audios',
                'title' => 'In Progress',
                'value' => $inProgress,
            ],
            [
                'key' => 'remaining_audios',
                'title' => 'Remaining',
                'value' => $remaining,
            ],
        ];
    }

    public function getAllCategories(): Collection
    {
        return AudioCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function createCategory(array $data): AudioCategory
    {
        return DB::transaction(function () use ($data) {
            return AudioCategory::query()->create([
                'name' => $data['name'],
                'slug' => $this->generateUniqueCategorySlug($data['name']),
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    public function updateCategory(AudioCategory $category, array $data): AudioCategory
    {
        return DB::transaction(function () use ($category, $data) {
            $payload = [
                'name' => $data['name'] ?? $category->name,
                'sort_order' => $data['sort_order'] ?? $category->sort_order,
            ];

            if (array_key_exists('name', $data) && $data['name'] !== $category->name) {
                $payload['slug'] = $this->generateUniqueCategorySlug($data['name'], $category->id);
            }

            $category->update($payload);

            return $category->fresh();
        });
    }

    public function deleteCategory(AudioCategory $category): void
    {
        if ($category->audios()->exists()) {
            throw ValidationException::withMessages([
                'audio_category_id' => ['Cannot delete category with linked audio content.'],
            ]);
        }

        $category->delete();
    }

    public function getAllForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Audio::query()
            ->with('audioCategory')
            ->orderByDesc('id');

        if (! empty($filters['audio_category_id'])) {
            $query->where('audio_category_id', $filters['audio_category_id']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where('name', 'like', $search);
        }

        return $query->paginate($perPage);
    }

    public function createAudio(array $data): Audio
    {
        return DB::transaction(function () use ($data) {
            $audioPath = isset($data['audio_file']) && $data['audio_file'] instanceof UploadedFile
                ? $this->storeAudioFile($data['audio_file'])
                : null;

            $thumbnailPath = isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile
                ? $this->storeThumbnail($data['thumbnail'])
                : null;

            $audio = Audio::query()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'audio_category_id' => $data['audio_category_id'],
                'duration' => $data['duration'] ?? null,
                'local_path' => $audioPath,
                'thumbnail_path' => $thumbnailPath,
            ]);

            return $audio->load('audioCategory');
        });
    }

    public function updateAudio(Audio $audio, array $data): Audio
    {
        return DB::transaction(function () use ($audio, $data) {
            $payload = [
                'name' => $data['name'] ?? $audio->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $audio->description,
                'audio_category_id' => $data['audio_category_id'] ?? $audio->audio_category_id,
                'duration' => array_key_exists('duration', $data) ? $data['duration'] : $audio->duration,
            ];

            if (isset($data['audio_file']) && $data['audio_file'] instanceof UploadedFile) {
                $payload['local_path'] = $this->storeAudioFile($data['audio_file']);
                $this->deleteStoredFile($audio->local_path);
            }

            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $payload['thumbnail_path'] = $this->storeThumbnail($data['thumbnail']);
                $this->deleteThumbnail($audio->thumbnail_path);
            }

            $audio->update($payload);

            return $audio->fresh()->load('audioCategory');
        });
    }

    public function deleteAudio(Audio $audio): void
    {
        $audio->delete();
    }

    public function getAllForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Audio::query()
            ->whereHas('assignments', fn ($q) => $q->where('user_id', $user->id))
            ->with([
                'audioCategory',
                'progress' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->orderByDesc('id');

        if (! empty($filters['audio_category_id'])) {
            $query->where('audio_category_id', $filters['audio_category_id']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where('name', 'like', $search);
        }

        return $query->paginate($perPage);
    }

    public function getStreamPath(User $user, Audio $audio): string
    {
        if (! $this->canAccessAudio($user, $audio->id)) {
            throw new AuthorizationException('You are not allowed to stream this audio.');
        }

        if (! $audio->local_path) {
            throw ValidationException::withMessages([
                'audio' => ['Audio source file is not available.'],
            ]);
        }

        if (! Storage::disk('local')->exists($audio->local_path)) {
            throw ValidationException::withMessages([
                'audio' => ['Audio source file is missing from storage.'],
            ]);
        }

        return $audio->local_path;
    }

    public function assignAudioToUsers(int $audioId, array $userIds, int $assignedBy, bool $sendNotification = true): array
    {
        $audio = Audio::query()->findOrFail($audioId);
        $assignedByUser = User::query()->findOrFail($assignedBy);

        $normalizedUserIds = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($normalizedUserIds === []) {
            throw ValidationException::withMessages([
                'user_ids' => ['At least one valid user must be provided.'],
            ]);
        }

        $createdAssignments = DB::transaction(function () use ($audioId, $normalizedUserIds, $assignedBy) {
            $existingUserIds = AudioAssignment::query()
                ->where('audio_id', $audioId)
                ->whereIn('user_id', $normalizedUserIds)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $userIdsToCreate = array_values(array_diff($normalizedUserIds, $existingUserIds));
            $createdAssignmentIds = [];

            foreach ($userIdsToCreate as $userId) {
                $assignment = AudioAssignment::query()->create([
                    'audio_id' => $audioId,
                    'user_id' => $userId,
                    'assigned_by' => $assignedBy,
                    'assigned_at' => now(),
                    'notification_sent' => false,
                ]);

                $createdAssignmentIds[] = $assignment->id;
            }

            return [
                'created' => AudioAssignment::query()
                ->with(['audio', 'user.manager', 'user.department', 'assignedBy'])
                ->whereIn('id', $createdAssignmentIds)
                ->get(),
                'skipped_user_ids' => $existingUserIds,
            ];
        });

        $created = $createdAssignments['created'];

        if ($sendNotification && $created->isNotEmpty()) {
            Log::info('Dispatching AudioAssigned event for queued assignment notifications.', [
                'audio_id' => (int) $audio->id,
                'assigned_by_id' => (int) $assignedByUser->id,
                'assignment_ids' => $created->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'queue_connection' => (string) config('queue.default'),
                'mail_mailer' => (string) config('mail.default'),
            ]);

            AudioAssigned::dispatch(
                audioId: (int) $audio->id,
                assignmentIds: $created->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                assignedById: (int) $assignedByUser->id,
            );
        } else {
            Log::info('Skipping AudioAssigned event dispatch.', [
                'audio_id' => (int) $audio->id,
                'assigned_by_id' => (int) $assignedByUser->id,
                'send_notification' => $sendNotification,
                'created_assignments_count' => $created->count(),
                'skipped_user_ids' => $createdAssignments['skipped_user_ids'],
            ]);
        }

        return [
            'created' => $created->fresh(['audio', 'user.manager', 'user.department', 'assignedBy']),
            'skipped_user_ids' => $createdAssignments['skipped_user_ids'],
        ];
    }

    public function removeAssignment(AudioAssignment $assignment): void
    {
        $assignment->delete();
    }

    public function getAssignmentsList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AudioAssignment::query()
            ->with(['audio', 'user.manager', 'assignedBy'])
            ->orderByDesc('assigned_at');

        if (! empty($filters['audio_id'])) {
            $query->where('audio_id', $filters['audio_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';

            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($sub) => $sub->where('name', 'like', $search)->orWhere('email', 'like', $search))
                    ->orWhereHas('audio', fn ($sub) => $sub->where('name', 'like', $search));
            });
        }

        return $query->paginate($perPage);
    }

    public function getOrCreateProgress(int $userId, int $audioId): AudioProgress
    {
        return AudioProgress::query()->firstOrCreate(
            [
                'user_id' => $userId,
                'audio_id' => $audioId,
            ],
            [
                'current_time' => 0,
                'total_listened_time' => 0,
                'is_completed' => false,
                'completion_percentage' => 0,
            ]
        );
    }

    public function updateProgressBatch(int $userId, int $audioId, array $groupedPayload): AudioProgress
    {
        $audio = Audio::query()->findOrFail($audioId);

        if (! $this->isAudioAssignedToUser($userId, $audioId)) {
            throw new AuthorizationException('Audio is not assigned to this user.');
        }

        $chunks = $groupedPayload['chunks'] ?? [];
        if ($chunks === []) {
            throw ValidationException::withMessages([
                'chunks' => ['Progress chunks are required.'],
            ]);
        }

        $batchKey = isset($groupedPayload['batch_key']) ? (string) $groupedPayload['batch_key'] : null;
        $idempotencyKey = null;

        if ($batchKey) {
            $idempotencyKey = sprintf('audio_progress:%d:%d:%s', $userId, $audioId, $batchKey);

            // Prevent duplicate batch processing when the frontend retries the same payload.
            if (! Cache::add($idempotencyKey, 'processing', now()->addMinutes(10))) {
                return $this->getOrCreateProgress($userId, $audioId)->fresh(['audio', 'user']);
            }
        }

        try {
            $progress = DB::transaction(function () use ($userId, $audioId, $audio, $chunks) {
                $progress = AudioProgress::query()
                    ->where('user_id', $userId)
                    ->where('audio_id', $audioId)
                    ->lockForUpdate()
                    ->first();

                if (! $progress) {
                    $progress = AudioProgress::query()->create([
                        'user_id' => $userId,
                        'audio_id' => $audioId,
                        'current_time' => 0,
                        'total_listened_time' => 0,
                        'is_completed' => false,
                        'completion_percentage' => 0,
                    ]);
                }

                $maxCurrentTime = (float) $progress->current_time;
                $deltaListened = 0;

                foreach ($chunks as $chunk) {
                    $chunkCurrent = isset($chunk['current_time']) ? (float) $chunk['current_time'] : 0;
                    $chunkListened = isset($chunk['listened_time']) ? (int) $chunk['listened_time'] : 0;

                    if ($chunkCurrent < 0 || $chunkListened < 0) {
                        continue;
                    }

                    // Cap per-chunk listened time to mitigate abusive payloads.
                    $deltaListened += min($chunkListened, 3600);
                    $maxCurrentTime = max($maxCurrentTime, $chunkCurrent);
                }

                $duration = (int) ($audio->duration ?? 0);
                if ($duration > 0) {
                    $maxCurrentTime = min($maxCurrentTime, (float) $duration);
                }

                $computedCompletion = $duration > 0
                    ? round(($maxCurrentTime / max($duration, 1)) * 100, 2)
                    : (float) $progress->completion_percentage;

                $computedCompletion = min($computedCompletion, 100.00);
                $computedCompletion = max($computedCompletion, (float) $progress->completion_percentage);

                $isCompleted = (bool) $progress->is_completed || $computedCompletion >= self::AUTO_COMPLETE_THRESHOLD;
                if ($isCompleted) {
                    $computedCompletion = 100.00;
                }

                $progress->update([
                    'current_time' => $maxCurrentTime,
                    'total_listened_time' => (int) $progress->total_listened_time + $deltaListened,
                    'completion_percentage' => $computedCompletion,
                    'is_completed' => $isCompleted,
                    'last_accessed_at' => now(),
                ]);

                return $progress->fresh(['audio', 'user']);
            });

            if ($idempotencyKey) {
                Cache::put($idempotencyKey, 'completed', now()->addDay());
            }

            return $progress;
        } catch (Throwable $exception) {
            if ($idempotencyKey) {
                Cache::forget($idempotencyKey);
            }

            throw $exception;
        }
    }

    public function findAudioForAdminOrFail(int $audioId): Audio
    {
        return Audio::query()
            ->with([
                'audioCategory',
                'assignments.user',
                'progress',
            ])
            ->findOrFail($audioId);
    }

    public function findAudioForUserOrFail(User $user, int $audioId): Audio
    {
        $query = Audio::query()
            ->with([
                'audioCategory',
                'progress' => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->whereKey($audioId);

        if (! $user->isAdmin()) {
            $query->whereHas('assignments', fn ($q) => $q->where('user_id', $user->id));
        }

        return $query->firstOrFail();
    }

    private function canAccessAudio(User $user, int $audioId): bool
    {
        return $user->isAdmin() || $this->isAudioAssignedToUser($user->id, $audioId);
    }

    private function isAudioAssignedToUser(int $userId, int $audioId): bool
    {
        return AudioAssignment::query()
            ->where('user_id', $userId)
            ->where('audio_id', $audioId)
            ->exists();
    }

    private function storeAudioFile(UploadedFile $file): string
    {
        return $file->store('audios/files', 'local');
    }

    private function storeThumbnail(UploadedFile $file): string
    {
        return $file->store('audios/thumbnails', 'public');
    }

    private function deleteThumbnail(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteStoredFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    private function generateUniqueCategorySlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'audio-category';

        $slug = $base;
        $counter = 1;

        while ($this->categorySlugExists($slug, $ignoreId)) {
            $counter++;
            $slug = $base . '-' . $counter;
        }

        return $slug;
    }

    private function categorySlugExists(string $slug, ?int $ignoreId = null): bool
    {
        return AudioCategory::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();
    }
}
