<?php

namespace App\Services\OnlineCourse;

use App\Models\CourseAnalytics;
use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\ModuleContent;
use App\Models\ModuleContentPdf;
use App\Models\User;
use App\Support\Filtering\FilterableQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnlineCourseService
{
    use FilterableQuery;

    public function getAllForAdmin(array $params = []): LengthAwarePaginator
    {
        $query = CourseOnline::query()
            ->withCount(['modules', 'assignments'])
            ->with(['creator']);

        return $this->applyFilters($query, $params, [
            'searchable'  => ['name'],
            'filters'     => ['status' => 'exact'],
            'dateColumn'  => 'created_at',
            'sortable'    => ['name', 'created_at'],
            'defaultSort' => ['created_at', 'desc'],
            'perPage'     => 15,
        ]);
    }

    public function getCourseById(int $id): CourseOnline
    {
        return CourseOnline::query()
            ->with([
                'creator',
                'modules.contents.video',
                'modules.contents.pdf',
                'modules.quiz',
            ])
            ->findOrFail($id);
    }

    public function createCourse(array $data, User $admin): CourseOnline
    {
        return DB::transaction(function () use ($data, $admin) {
            $modules = $data['modules'] ?? [];
            unset($data['modules']);

            if (isset($data['image_file']) && $data['image_file'] instanceof UploadedFile) {
                $data['image_path'] = $this->storeImage($data['image_file']);
            }
            unset($data['image_file']);

            $data['created_by'] = $admin->id;
            $data['status']     = $data['status'] ?? 'draft';

            /** @var CourseOnline $course */
            $course = CourseOnline::query()->create($data);

            // Auto-create analytics stub row (all zeros via column defaults)
            CourseAnalytics::query()->create([
                'course_online_id' => $course->id,
            ]);

            foreach ($modules as $moduleData) {
                $this->createModule($course->id, $moduleData);
            }

            return $this->getCourseById($course->id);
        });
    }

    public function updateCourse(int $id, array $data, User $admin): CourseOnline
    {
        return DB::transaction(function () use ($id, $data) {
            /** @var CourseOnline $course */
            $course = CourseOnline::query()->findOrFail($id);

            $modules = null;
            if (array_key_exists('modules', $data)) {
                $modules = $data['modules'];
                unset($data['modules']);
            }

            if (isset($data['image_file']) && $data['image_file'] instanceof UploadedFile) {
                if ($course->image_path && Storage::disk('public')->exists($course->image_path)) {
                    Storage::disk('public')->delete($course->image_path);
                }
                $data['image_path'] = $this->storeImage($data['image_file']);
            }
            unset($data['image_file']);

            unset($data['created_by']);
            $course->update($data);

            if ($modules !== null) {
                $this->syncModules($course, $modules);
            }

            return $this->getCourseById($course->id);
        });
    }

    public function deleteCourse(int $id): void
    {
        /** @var CourseOnline $course */
        $course = CourseOnline::query()->findOrFail($id);

        // Block deletion if course has assignments
        if ($course->assignments()->exists()) {
            abort(422, 'Cannot delete a course with active assignments.');
        }

        DB::transaction(function () use ($course) {
            $course->load('modules.contents.pdf');

            foreach ($course->modules as $module) {
                $this->deleteModuleWithContent($module);
            }

            $course->delete();
        });
    }

    private function storeImage(UploadedFile $file): string
    {
        $uuid      = (string) Str::uuid();
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $path      = "course-images/{$uuid}_{$sanitized}";

        Storage::disk('public')->put($path, $file->getContent());

        return $path;
    }

    private function storeThumbnail(UploadedFile $file): string
    {
        $uuid      = (string) Str::uuid();
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $path      = "course-thumbnails/{$uuid}_{$sanitized}";

        Storage::disk('public')->put($path, $file->getContent());

        return $path;
    }

    private function uploadPdf(UploadedFile $file): array
    {
        $uuid      = (string) Str::uuid();
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $path      = "course-pdfs/{$uuid}_{$sanitized}";

        Storage::disk('public')->put($path, $file->getContent());

        return [
            'file_path'  => $path,
            'file_size'  => $file->getSize(),
        ];
    }

    private function storeAttachment(UploadedFile $file): array
    {
        $uuid      = (string) Str::uuid();
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $path      = "course-attachments/{$uuid}_{$sanitized}";

        Storage::disk('public')->put($path, $file->getContent());

        return [
            'attachment_path'      => $path,
            'attachment_name'      => $file->getClientOriginalName(),
            'attachment_extension' => $file->getClientOriginalExtension(),
        ];
    }

    public function reorderModules(array $data): void
    {
        $order     = $data['order'];
        $moduleIds = collect($order)->pluck('module_id')->toArray();

        $modules = CourseModule::query()->whereIn('id', $moduleIds)->get();

        if ($modules->count() !== count($moduleIds)) {
            abort(422, 'One or more modules not found.');
        }

        $courseIds = $modules->pluck('course_online_id')->unique();
        if ($courseIds->count() > 1) {
            abort(422, 'All modules must belong to the same course.');
        }

        DB::transaction(function () use ($order) {
            $offset = 100000;

            // First pass: temporary high numbers to avoid unique constraint conflicts
            foreach ($order as $item) {
                CourseModule::query()
                    ->where('id', $item['module_id'])
                    ->update(['order_number' => $item['order_number'] + $offset]);
            }

            // Second pass: set final order numbers
            foreach ($order as $item) {
                CourseModule::query()
                    ->where('id', $item['module_id'])
                    ->update(['order_number' => $item['order_number']]);
            }
        });
    }

    public function getAdminCourseCards(): array
    {
        $total       = CourseOnline::query()->count();
        $published   = CourseOnline::query()->where('status', 'published')->count();
        $draft       = CourseOnline::query()->where('status', 'draft')->count();
        $enrollments = CourseOnlineAssignment::query()->count();
        return [
            ['key' => 'total_courses',    'title' => 'Total Courses',     'value' => $total],
            ['key' => 'published_courses', 'title' => 'Published Courses', 'value' => $published],
            ['key' => 'draft_courses',     'title' => 'Draft Courses',     'value' => $draft],
            ['key' => 'total_enrollments', 'title' => 'Total Enrollments', 'value' => $enrollments],
        ];
    }

    public function getCourseEnrollments(int $courseId, array $filters = [], int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = DB::table('course_online_assignments as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->leftJoin('users as ab', 'ab.id', '=', 'a.assigned_by')
            ->leftJoin('user_course_progress as p', function ($join) use ($courseId) {
                $join->on('p.user_id', '=', 'a.user_id')
                     ->where('p.course_online_id', '=', $courseId);
            })
            ->where('a.course_online_id', $courseId)
            ->select(
                'u.id as user_id',
                'u.name as user_name',
                'u.email as user_email',
                'd.name as department',
                'a.assigned_at',
                'ab.name as assigned_by',
                DB::raw("COALESCE(p.status, 'not_started') as status"),
                DB::raw('COALESCE(p.progress_percentage, 0) as progress_percentage'),
                DB::raw('COALESCE(p.completed_content_items, 0) as completed_content_items'),
                DB::raw('COALESCE(p.total_content_items, 0) as total_content_items'),
                'p.started_at',
                'p.completed_at',
                'p.last_accessed_at'
            );

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('u.name', 'like', $term)
                  ->orWhere('u.email', 'like', $term);
            });
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'not_started') {
                $query->whereNull('p.status');
            } else {
                $query->where('p.status', $filters['status']);
            }
        }

        if (!empty($filters['department_id'])) {
            $query->where('u.department_id', $filters['department_id']);
        }

        return $query->orderByDesc('a.assigned_at')->paginate($perPage);
    }

    public function getCourseEnrollmentCards(int $courseId): array
    {
        $total      = DB::table('course_online_assignments')->where('course_online_id', $courseId)->count();
        $completed  = DB::table('user_course_progress')->where('course_online_id', $courseId)->where('status', 'completed')->count();
        $inProgress = DB::table('user_course_progress')->where('course_online_id', $courseId)->where('status', 'in_progress')->count();
        $notStarted = max(0, $total - $completed - $inProgress);

        return [
            ['key' => 'total_enrolled',  'title' => 'Total Enrolled',  'value' => $total],
            ['key' => 'not_started',     'title' => 'Not Started',     'value' => $notStarted],
            ['key' => 'in_progress',     'title' => 'In Progress',     'value' => $inProgress],
            ['key' => 'completed',       'title' => 'Completed',       'value' => $completed],
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function createModule(int $courseId, array $moduleData): CourseModule
    {
        $contents = $moduleData['contents'] ?? [];
        unset($moduleData['contents']);

        $moduleData['course_online_id'] = $courseId;

        /** @var CourseModule $module */
        $module = CourseModule::query()->create($moduleData);

        foreach ($contents as $contentData) {
            $this->createContent($module->id, $contentData);
        }

        return $module;
    }

    private function createContent(int $moduleId, array $contentData): ModuleContent
    {
        $pdfMeta = null;

        // Handle thumbnail upload
        if (isset($contentData['thumbnail_file']) && $contentData['thumbnail_file'] instanceof UploadedFile) {
            $contentData['thumbnail_path'] = $this->storeThumbnail($contentData['thumbnail_file']);
        }
        unset($contentData['thumbnail_file']);

        // Discard any client-supplied path strings — these are server-managed
        unset($contentData['attachment_path'], $contentData['attachment_name'], $contentData['attachment_extension']);

        // Handle direct PDF file upload (legacy pdf_file or nested pdf.file_path)
        $pdfUpload = $contentData['pdf_file'] ?? data_get($contentData, 'pdf.file_path');
        $pdfPageCount = data_get($contentData, 'pdf.pdf_page_count');
        if ($pdfPageCount === null) {
            $pdfPageCount = $contentData['pdf_page_count'] ?? null;
        }

        if ($pdfUpload instanceof UploadedFile) {
            $stored  = $this->uploadPdf($pdfUpload);
            $pdfMeta = [
                'file_path'      => $stored['file_path'],
                'pdf_page_count' => $pdfPageCount,
            ];
        } elseif (is_array($contentData['pdf'] ?? null)
            && array_key_exists('pdf_page_count', $contentData['pdf'])) {
            $pdfMeta = [
                'pdf_page_count' => $pdfPageCount,
            ];
        }
        unset($contentData['pdf_file'], $contentData['pdf_page_count'], $contentData['pdf']);

        // Handle direct attachment file upload (for video content)
        if (isset($contentData['attachment_file']) && $contentData['attachment_file'] instanceof UploadedFile) {
            $stored = $this->storeAttachment($contentData['attachment_file']);
            $contentData['attachment_path']      = $stored['attachment_path'];
            $contentData['attachment_name']      = $stored['attachment_name'];
            $contentData['attachment_extension'] = $stored['attachment_extension'];
            unset($contentData['attachment_file']);
        }

        $contentData['module_id'] = $moduleId;

        /** @var ModuleContent $content */
        $content = ModuleContent::query()->create($contentData);

        if ($pdfMeta && isset($pdfMeta['file_path']) && $content->content_type === 'pdf') {
            ModuleContentPdf::query()->create([
                'module_content_id' => $content->id,
                'file_path'         => $pdfMeta['file_path'],
                'pdf_page_count'    => $pdfMeta['pdf_page_count'] ?? null,
            ]);
        }

        return $content;
    }

    private function syncModules(CourseOnline $course, array $modules): void
    {
        $existingIds = $course->modules()->pluck('id')->toArray();
        $incomingIds = collect($modules)->pluck('id')->filter()->map(fn ($v) => (int) $v)->toArray();
        $toDeleteIds = array_diff($existingIds, $incomingIds);

        foreach ($toDeleteIds as $moduleId) {
            $module = CourseModule::query()->with('contents.pdf')->find($moduleId);
            if ($module) {
                $this->deleteModuleWithContent($module);
            }
        }

        foreach ($modules as $moduleData) {
            if (!empty($moduleData['id'])) {
                $module   = CourseModule::query()->findOrFail($moduleData['id']);
                $contents = null;
                if (array_key_exists('contents', $moduleData)) {
                    $contents = $moduleData['contents'];
                }
                unset($moduleData['contents'], $moduleData['id']);
                $module->update($moduleData);

                if ($contents !== null) {
                    $this->syncModuleContent($module, $contents);
                }
            } else {
                $this->createModule($course->id, $moduleData);
            }
        }
    }

    private function syncModuleContent(CourseModule $module, array $contents): void
    {
        $existingIds = $module->contents()->pluck('id')->toArray();
        $incomingIds = collect($contents)->pluck('id')->filter()->map(fn ($v) => (int) $v)->toArray();
        $toDeleteIds = array_diff($existingIds, $incomingIds);

        foreach ($toDeleteIds as $contentId) {
            $content = ModuleContent::query()->with('pdf')->find($contentId);
            if ($content) {
                $this->deleteContentWithPdf($content);
            }
        }

        foreach ($contents as $contentData) {
            if (!empty($contentData['id'])) {
                $content = ModuleContent::query()->findOrFail($contentData['id']);

                // --- Handle PDF file replacement ---
                $pdfMeta = null;
                $pdfUpload = $contentData['pdf_file'] ?? data_get($contentData, 'pdf.file_path');
                $pdfPageCount = data_get($contentData, 'pdf.pdf_page_count');
                if ($pdfPageCount === null) {
                    $pdfPageCount = $contentData['pdf_page_count'] ?? null;
                }

                if ($pdfUpload instanceof UploadedFile) {
                    $existingPdf = $content->pdf()->first();
                    if ($existingPdf && Storage::disk('local')->exists($existingPdf->file_path)) {
                        Storage::disk('local')->delete($existingPdf->file_path);
                    }
                    $stored  = $this->uploadPdf($pdfUpload);
                    $pdfMeta = [
                        'file_path'      => $stored['file_path'],
                        'pdf_page_count' => $pdfPageCount,
                    ];
                } elseif (is_array($contentData['pdf'] ?? null)
                    && array_key_exists('pdf_page_count', $contentData['pdf'])) {
                    $pdfMeta = [
                        'pdf_page_count' => $pdfPageCount,
                    ];
                }
                unset($contentData['pdf_file'], $contentData['pdf_page_count'], $contentData['pdf']);

                // --- Handle attachment file replacement ---
                if (isset($contentData['attachment_file']) && $contentData['attachment_file'] instanceof UploadedFile) {
                    if ($content->attachment_path && Storage::disk('public')->exists($content->attachment_path)) {
                        Storage::disk('public')->delete($content->attachment_path);
                    }
                    $stored = $this->storeAttachment($contentData['attachment_file']);
                    $contentData['attachment_path']      = $stored['attachment_path'];
                    $contentData['attachment_name']      = $stored['attachment_name'];
                    $contentData['attachment_extension'] = $stored['attachment_extension'];
                }
                unset($contentData['attachment_file']);

                // Handle thumbnail replacement
                if (isset($contentData['thumbnail_file']) && $contentData['thumbnail_file'] instanceof UploadedFile) {
                    if ($content->thumbnail_path && Storage::disk('public')->exists($content->thumbnail_path)) {
                        Storage::disk('public')->delete($content->thumbnail_path);
                    }
                    $contentData['thumbnail_path'] = $this->storeThumbnail($contentData['thumbnail_file']);
                }
                unset($contentData['thumbnail_file']);

                // Discard any client-supplied path strings — server-managed only
                unset($contentData['attachment_path'], $contentData['attachment_name'], $contentData['attachment_extension']);

                // content_type is immutable — never overwrite
                unset($contentData['id'], $contentData['content_type']);
                $content->update($contentData);

                if ($content->content_type === 'pdf' && $pdfMeta) {
                    $existingPdf = $content->pdf()->first();

                    if ($existingPdf) {
                        $updates = [];
                        if (array_key_exists('pdf_page_count', $pdfMeta)) {
                            $updates['pdf_page_count'] = $pdfMeta['pdf_page_count'];
                        }
                        if (isset($pdfMeta['file_path'])) {
                            $updates['file_path'] = $pdfMeta['file_path'];
                        }

                        if ($updates !== []) {
                            $existingPdf->update($updates);
                        }
                    } elseif (isset($pdfMeta['file_path'])) {
                        ModuleContentPdf::query()->create([
                            'module_content_id' => $content->id,
                            'file_path'         => $pdfMeta['file_path'],
                            'pdf_page_count'    => $pdfMeta['pdf_page_count'] ?? null,
                        ]);
                    }
                }
            } else {
                $this->createContent($module->id, $contentData);
            }
        }
    }

    private function deleteModuleWithContent(CourseModule $module): void
    {
        if (!$module->relationLoaded('contents')) {
            $module->load('contents.pdf');
        }

        foreach ($module->contents as $content) {
            $this->deleteContentWithPdf($content);
        }

        $module->delete();
    }

    private function deleteContentWithPdf(ModuleContent $content): void
    {
        if ($content->content_type === 'pdf') {
            $pdf = $content->relationLoaded('pdf') ? $content->pdf : $content->pdf()->first();
            if ($pdf) {
                if (Storage::disk('public')->exists($pdf->file_path)) {
                    Storage::disk('public')->delete($pdf->file_path);
                }
                $pdf->delete();
            }
        }

        if ($content->attachment_path && Storage::disk('local')->exists($content->attachment_path)) {
            Storage::disk('local')->delete($content->attachment_path);
        }

        $content->delete();
    }
}