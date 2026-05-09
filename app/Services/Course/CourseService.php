<?php

namespace App\Services\Course;

use App\Events\CourseAssigned;
use App\Events\PrivacyChangedToPublic;
use App\Events\PublicCourseCreated;
use App\Events\UserEnrolledInPublicCourse;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseAvailability;
use App\Models\CourseCompletion;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CourseService
{
    public function getAdminCourseCards(): array
    {
        return [
            [
                'key' => 'total_courses',
                'title' => 'Total Courses',
                'value' => Course::query()->count(),
            ],
            [
                'key' => 'published_courses',
                'title' => 'Published Courses',
                'value' => Course::query()->where('status', 'published')->count(),
            ],
            [
                'key' => 'public_courses',
                'title' => 'Public Courses',
                'value' => Course::query()->where('privacy', 'public')->count(),
            ],
            [
                'key' => 'total_course_registrations',
                'title' => 'Total Course Registrations',
                'value' => CourseRegistration::query()->count(),
            ],
        ];
    }

    public function getAdminCourseAssignmentCards(): array
    {
        return [
            [
                'key' => 'total_course_assignments',
                'title' => 'Total Course Assignments',
                'value' => CourseAssignment::query()->count(),
            ],
            [
                'key' => 'assigned_users',
                'title' => 'Users With Course Assignments',
                'value' => CourseAssignment::query()->distinct('user_id')->count('user_id'),
            ],
            [
                'key' => 'assigned_courses',
                'title' => 'Courses With Assignments',
                'value' => CourseAssignment::query()->distinct('course_id')->count('course_id'),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Admin: Courses
    // -------------------------------------------------------------------------

    public function getAllCoursesForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = Course::query()
            ->with(['availabilities'])
            ->withCount('registrations')
            ->orderByDesc('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['privacy'])) {
            $query->where('privacy', $filters['privacy']);
        }

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where('name', 'like', $search);
        }

        return $query->paginate(15);
    }

    public function getCourseByIdForAdmin(int $id): Course
    {
        return Course::query()
            ->with(['availabilities', 'creator'])
            ->withCount('registrations')
            ->findOrFail($id);
    }

    public function createCourse(array $data, User $admin): Course
    {
        $imagePath = $this->storeImageIfPresent($data['image'] ?? null);

        $course = DB::transaction(function () use ($data, $admin, $imagePath) {
            $course = Course::query()->create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'image_path'  => $imagePath,
                'level'       => $data['level'] ?? null,
                'duration'    => $data['duration'] ?? null,
                'status'      => $data['status'],
                'privacy'     => $data['privacy'],
                'created_by'  => $admin->id,
            ]);

            foreach ($data['availabilities'] as $avData) {
                $this->createAvailability($course->id, $avData);
            }

            return $course;
        });

        if ($data['privacy'] === 'public') {
            PublicCourseCreated::dispatch($course);
        }

        return $course->load('availabilities');
    }

    public function updateCourse(int $id, array $data, User $admin): Course
    {
        $course = Course::query()->findOrFail($id);

        $wasPrivate = $course->privacy === 'private';
        $isNowPublic = isset($data['privacy']) && $data['privacy'] === 'public';

        // Collect user IDs already assigned before any changes
        $assignedUserIds = $course->assignments()->pluck('user_id')->all();

        DB::transaction(function () use ($course, $data, &$updatedCourse) {
            $payload = array_filter([
                'name'        => $data['name'] ?? null,
                'description' => array_key_exists('description', $data) ? $data['description'] : null,
                'level'       => array_key_exists('level', $data) ? $data['level'] : null,
                'duration'    => array_key_exists('duration', $data) ? $data['duration'] : null,
                'status'      => $data['status'] ?? null,
                'privacy'     => $data['privacy'] ?? null,
            ], fn ($v) => ! is_null($v));

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $this->deleteStoredImage($course->image_path);
                $payload['image_path'] = $this->storeImageIfPresent($data['image']);
            }

            $course->update($payload);

            if (isset($data['availabilities'])) {
                $submittedIds = collect($data['availabilities'])
                    ->pluck('id')
                    ->filter()
                    ->values()
                    ->all();

                // Auto-close omitted availabilities only if they have no registrations
                $course->availabilities()
                    ->where('status', 'active')
                    ->whereNotIn('id', $submittedIds)
                    ->whereDoesntHave('registrations')
                    ->update(['status' => 'closed']);

                foreach ($data['availabilities'] as $avData) {
                    if (! empty($avData['id'])) {
                        $availability = CourseAvailability::query()->findOrFail($avData['id']);
                        $availability->update($this->buildAvailabilityPayload($avData));
                    } else {
                        $this->createAvailability($course->id, $avData);
                    }
                }
            }

            $updatedCourse = $course;
        });

        if ($wasPrivate && $isNowPublic) {
            PrivacyChangedToPublic::dispatch($course->fresh(), $assignedUserIds);
        }

        return $course->fresh()->load('availabilities');
    }

    public function deleteCourse(int $id): void
    {
        $course = Course::query()->findOrFail($id);

        if ($course->image_path) {
            $this->deleteStoredImage($course->image_path);
        }

        $course->delete();
    }

    // -------------------------------------------------------------------------
    // Admin: Course Assignments
    // -------------------------------------------------------------------------

    public function getAllAssignmentsForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = CourseAssignment::query()
            ->with(['course', 'user', 'assignedBy'])
            ->orderByDesc('assigned_at');

        if (! empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->paginate(15);
    }

    public function assignCourseToUser(int $courseId, int $userId, ?int $availabilityId, User $admin): CourseAssignment
    {
        $course = Course::query()->findOrFail($courseId);
        $user   = User::query()->findOrFail($userId);

        $exists = CourseAssignment::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'user_id' => ['This course is already assigned to the specified user.'],
            ]);
        }

        $alreadyRegistered = CourseRegistration::query()
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyRegistered) {
            throw ValidationException::withMessages([
                'user_id' => ['This user is already enrolled in the specified course.'],
            ]);
        }

        $assignment = DB::transaction(function () use ($courseId, $userId, $availabilityId, $admin) {
            if ($availabilityId !== null) {
                /** @var CourseAvailability $availability */
                $availability = CourseAvailability::query()
                    ->lockForUpdate()
                    ->findOrFail($availabilityId);

                if ((int) $availability->course_id !== $courseId) {
                    throw ValidationException::withMessages([
                        'course_availability_id' => ['The specified availability does not belong to this course.'],
                    ]);
                }

                if ($availability->status !== 'active') {
                    throw ValidationException::withMessages([
                        'course_availability_id' => ['This availability is no longer active.'],
                    ]);
                }

                if ($availability->end_date && now()->gt($availability->end_date->endOfDay())) {
                    throw ValidationException::withMessages([
                        'course_availability_id' => ['This availability has already ended.'],
                    ]);
                }

                $assignedCount = CourseAssignment::query()
                    ->where('course_availability_id', $availabilityId)
                    ->count();
                $registeredCount = CourseRegistration::query()
                    ->where('course_availability_id', $availabilityId)
                    ->count();
                $usedSeats = $assignedCount + $registeredCount;
                $capacity = (int) ($availability->capacity ?? 0);

                if ($capacity > 0 && $usedSeats >= $capacity) {
                    throw ValidationException::withMessages([
                        'course_availability_id' => ['No seats are available in this session.'],
                    ]);
                }
            }

            return CourseAssignment::query()->create([
                'course_id'               => $courseId,
                'user_id'                 => $userId,
                'assigned_by'             => $admin->id,
                'course_availability_id'  => $availabilityId,
                'assigned_at'             => now(),
            ]);
        });

        CourseAssigned::dispatch($course, $user, $admin);

        return $assignment->load(['course', 'user', 'assignedBy']);
    }

    public function removeAssignment(int $id): void
    {
        $assignment = CourseAssignment::query()->findOrFail($id);
        $assignment->delete();
    }

    // -------------------------------------------------------------------------
    // User: Courses
    // -------------------------------------------------------------------------

    public function getCoursesForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $assignedCourseIds = CourseAssignment::query()
            ->where('user_id', $user->id)
            ->pluck('course_id')
            ->all();

        $query = Course::query()
            ->where(function ($q) use ($assignedCourseIds) {
                $q->where('privacy', 'public')
                    ->orWhereIn('id', $assignedCourseIds);
            })
            ->where('status', '!=', 'archived')
            ->with([
                'availabilities',
                'registrations' => fn ($q) => $q->where('user_id', $user->id),
                'assignments'   => fn ($q) => $q->where('user_id', $user->id),
            ])
            ->orderByDesc('id');

        if (! empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where('name', 'like', $search);
        }

        return $query->paginate(10);
    }

    public function getCourseDetail(int $courseId, User $user): Course
    {
        $course = Course::query()->findOrFail($courseId);

        $isPublic    = $course->privacy === 'public';
        $isAssigned  = CourseAssignment::query()
            ->where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isPublic && ! $isAssigned) {
            throw ValidationException::withMessages([
                'course_id' => ['You do not have access to this course.'],
            ]);
        }

        $course->load([
            'availabilities' => fn ($q) => $q->orderBy('start_date'),
            'registrations'  => fn ($q) => $q->where('user_id', $user->id),
        ]);

        return $course;
    }

    public function enrollUserInCourse(int $courseId, int $availabilityId, User $user): CourseRegistration
    {
        $registration = DB::transaction(function () use ($courseId, $availabilityId, $user) {
            /** @var CourseAvailability $availability */
            $availability = CourseAvailability::query()
                ->lockForUpdate()
                ->findOrFail($availabilityId);

            if ((int) $availability->course_id !== $courseId) {
                throw ValidationException::withMessages([
                    'course_availability_id' => ['The specified availability does not belong to this course.'],
                ]);
            }

            if ($availability->status !== 'active') {
                throw ValidationException::withMessages([
                    'course_availability_id' => ['This availability is no longer active.'],
                ]);
            }

            if (now()->gt($availability->end_date->endOfDay())) {
                throw ValidationException::withMessages([
                    'course_availability_id' => ['This availability has already ended.'],
                ]);
            }

            $alreadyEnrolled = CourseRegistration::query()
                ->where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->exists();

            if ($alreadyEnrolled) {
                throw ValidationException::withMessages([
                    'course_id' => ['You are already enrolled in this course.'],
                ]);
            }

            $assignedCount = CourseAssignment::query()
                ->where('course_availability_id', $availabilityId)
                ->where('user_id', '!=', $user->id)
                ->count();
            $registeredCount = CourseRegistration::query()
                ->where('course_availability_id', $availabilityId)
                ->where('user_id', '!=', $user->id)
                ->count();
            $usedSeats = $assignedCount + $registeredCount;
            $capacity = (int) ($availability->capacity ?? 0);

            if ($capacity > 0 && $usedSeats >= $capacity) {
                throw ValidationException::withMessages([
                    'course_availability_id' => ['No seats are available in this session.'],
                ]);
            }

            $registration = CourseRegistration::query()->create([
                'user_id'                 => $user->id,
                'course_id'               => $courseId,
                'course_availability_id'  => $availabilityId,
                'status'                  => 'in_progress',
                'registered_at'           => now(),
            ]);

            return $registration;
        });

        $course = Course::query()->find($courseId);

        if ($course && $course->privacy === 'public') {
            UserEnrolledInPublicCourse::dispatch($course, $user);
        }

        return $registration->load(['course', 'availability']);
    }

    public function completeCourse(int $courseId, User $user): CourseRegistration
    {
        $registration = CourseRegistration::query()
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->firstOrFail();

        if ($registration->status === 'completed') {
            throw ValidationException::withMessages([
                'course_id' => ['You have already completed this course.'],
            ]);
        }

        if ($registration->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'course_id' => ['You must be enrolled in a course before completing it.'],
            ]);
        }

        DB::transaction(function () use ($registration, $courseId, $user) {
            $registration->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            CourseCompletion::query()->updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $courseId],
                ['completed_at' => now()]
            );
        });

        return $registration->fresh()->load(['course', 'availability']);
    }

    public function submitRating(int $courseId, int $rating, ?string $feedback, User $user): CourseRegistration
    {
        $registration = CourseRegistration::query()
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->firstOrFail();

        if ($registration->status !== 'completed') {
            throw ValidationException::withMessages([
                'course_id' => ['You must complete the course before submitting a rating.'],
            ]);
        }

        DB::transaction(function () use ($registration, $courseId, $rating, $feedback, $user) {
            $registration->update([
                'rating'   => $rating,
                'feedback' => $feedback,
            ]);

            CourseCompletion::query()->updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $courseId],
                ['rating' => $rating, 'feedback' => $feedback, 'completed_at' => now()]
            );
        });

        return $registration->fresh()->load(['course', 'availability']);
    }

    public function getUserEnrollments(User $user): Collection
    {
        return CourseRegistration::query()
            ->where('user_id', $user->id)
            ->with(['course.availabilities', 'availability'])
            ->orderByDesc('registered_at')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAvailability(int $courseId, array $data): CourseAvailability
    {
        return CourseAvailability::query()->create(
            array_merge(['course_id' => $courseId], $this->buildAvailabilityPayload($data))
        );
    }

    private function buildAvailabilityPayload(array $data): array
    {
        $daysOfWeek = null;

        if (isset($data['days_of_week']) && is_array($data['days_of_week'])) {
            $daysOfWeek = implode(',', $data['days_of_week']);
        }

        return array_filter([
            'start_date'               => $data['start_date'] ?? null,
            'end_date'                 => $data['end_date'] ?? null,
            'capacity'                 => $data['capacity'] ?? null,
            'sessions'                 => $data['sessions'] ?? null,
            'duration_weeks'           => $data['duration_weeks'] ?? null,
            'status'                   => $data['status'] ?? 'active',
            'notes'                    => $data['notes'] ?? null,
            'days_of_week'             => $daysOfWeek,
            'session_time_shift_1'     => $data['session_time_shift_1'] ?? null,
            'session_time_shift_2'     => $data['session_time_shift_2'] ?? null,
            'session_time_shift_3'     => $data['session_time_shift_3'] ?? null,
            'session_duration_minutes' => $data['session_duration_minutes'] ?? null,
        ], fn ($v) => ! is_null($v));
    }

    private function storeImageIfPresent(?UploadedFile $image): ?string
    {
        if (! $image instanceof UploadedFile) {
            return null;
        }

        return $image->store('courses/images', 'public');
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}