<?php

namespace App\Services\OnlineCourse;

use App\Models\CourseAnalytics;
use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\ModuleContent;
use App\Models\ModuleContentPdf;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnlineCourseService
{
    public function getAllForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CourseOnline::query()
            ->withCount(['modules', 'assignments'])
            ->with(['creator'])
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
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

        // Block deletion if course has active assignments
        if ($course->assignments()->whereNull('deleted_at')->exists()) {
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

    private function uploadPdf(UploadedFile $file): array
    {
        $uuid      = (string) Str::uuid();
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $path      = "course-pdfs/{$uuid}_{$sanitized}";

        Storage::disk('local')->put($path, $file->getContent());

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

        Storage::disk('local')->put($path, $file->getContent());

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
        $enrollments = CourseOnlineAssignment::query()->whereNull('deleted_at')->count();

        return [
            ['key' => 'total_courses',    'title' => 'Total Courses',     'value' => $total],
            ['key' => 'published_courses', 'title' => 'Published Courses', 'value' => $published],
            ['key' => 'draft_courses',     'title' => 'Draft Courses',     'value' => $draft],
            ['key' => 'total_enrollments', 'title' => 'Total Enrollments', 'value' => $enrollments],
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

        // Handle direct PDF file upload
        if (isset($contentData['pdf_file']) && $contentData['pdf_file'] instanceof UploadedFile) {
            $stored  = $this->uploadPdf($contentData['pdf_file']);
            $pdfMeta = [
                'file_path'      => $stored['file_path'],
                'pdf_page_count' => $contentData['pdf_page_count'] ?? null,
            ];
            unset($contentData['pdf_file'], $contentData['pdf_page_count']);
        } elseif (isset($contentData['pdf'])) {
            $pdfMeta = $contentData['pdf'];
        }
        unset($contentData['pdf']);

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

        if ($pdfMeta && $content->content_type === 'pdf') {
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
                if (isset($contentData['pdf_file']) && $contentData['pdf_file'] instanceof UploadedFile) {
                    $existingPdf = $content->pdf()->first();
                    if ($existingPdf && Storage::disk('local')->exists($existingPdf->file_path)) {
                        Storage::disk('local')->delete($existingPdf->file_path);
                    }
                    $stored  = $this->uploadPdf($contentData['pdf_file']);
                    $pdfMeta = [
                        'file_path'      => $stored['file_path'],
                        'pdf_page_count' => $contentData['pdf_page_count'] ?? null,
                    ];
                } elseif (isset($contentData['pdf'])) {
                    $pdfMeta = $contentData['pdf'];

                    // Delete old file from storage if path changed
                    if ($pdfMeta) {
                        $existingPdf = $content->pdf;
                        if ($existingPdf
                            && isset($pdfMeta['file_path'])
                            && $existingPdf->file_path !== $pdfMeta['file_path']) {
                            if (Storage::disk('local')->exists($existingPdf->file_path)) {
                                Storage::disk('local')->delete($existingPdf->file_path);
                            }
                        }
                    }
                }
                unset($contentData['pdf_file'], $contentData['pdf_page_count'], $contentData['pdf']);

                // --- Handle attachment file replacement ---
                if (isset($contentData['attachment_file']) && $contentData['attachment_file'] instanceof UploadedFile) {
                    if ($content->attachment_path && Storage::disk('local')->exists($content->attachment_path)) {
                        Storage::disk('local')->delete($content->attachment_path);
                    }
                    $stored = $this->storeAttachment($contentData['attachment_file']);
                    $contentData['attachment_path']      = $stored['attachment_path'];
                    $contentData['attachment_name']      = $stored['attachment_name'];
                    $contentData['attachment_extension'] = $stored['attachment_extension'];
                }
                unset($contentData['attachment_file']);

                // content_type is immutable — never overwrite
                unset($contentData['id'], $contentData['content_type']);
                $content->update($contentData);

                if ($content->content_type === 'pdf' && $pdfMeta) {
                    $content->pdf()->updateOrCreate(
                        ['module_content_id' => $content->id],
                        [
                            'file_path'      => $pdfMeta['file_path'],
                            'pdf_page_count' => $pdfMeta['pdf_page_count'] ?? null,
                        ]
                    );
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
                if (Storage::disk('local')->exists($pdf->file_path)) {
                    Storage::disk('local')->delete($pdf->file_path);
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

