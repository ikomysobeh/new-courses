<?php

namespace App\Services\OnlineCourse;

use App\Models\CourseAnalytics;
use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\ModuleContent;
use App\Models\ModuleContentPdf;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                'modules.contents.pdf',
                'analytics',
            ])
            ->findOrFail($id);
    }

    public function createCourse(array $data, User $admin): CourseOnline
    {
        return DB::transaction(function () use ($data, $admin) {
            $modules = $data['modules'] ?? [];
            unset($data['modules']);

            $data['created_by'] = $admin->id;

            /** @var CourseOnline $course */
            $course = CourseOnline::query()->create($data);

            foreach ($modules as $moduleData) {
                $this->createModule($course->id, $moduleData);
            }

            CourseAnalytics::query()->create([
                'course_online_id' => $course->id,
                'total_modules'    => count($modules),
                'total_contents'   => collect($modules)->sum(fn($m) => count($m['contents'] ?? [])),
            ]);

            return $course->load(['creator', 'modules.contents.pdf', 'analytics']);
        });
    }

    public function updateCourse(int $id, array $data): CourseOnline
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

            $this->refreshAnalytics($course);

            return $course->load(['creator', 'modules.contents.pdf', 'analytics']);
        });
    }

    public function deleteCourse(int $id): void
    {
        DB::transaction(function () use ($id) {
            /** @var CourseOnline $course */
            $course = CourseOnline::query()->findOrFail($id);

            foreach ($course->modules as $module) {
                $this->deleteModuleWithContent($module);
            }

            $course->delete();
        });
    }

    public function uploadPdf(UploadedFile $file): array
    {
        $filename  = $file->getClientOriginalName();
        $path      = $file->store('online-courses/pdfs', 'local');
        $fileSize  = $file->getSize();

        return [
            'file_path'         => $path,
            'original_filename' => $filename,
            'file_size'         => $fileSize,
        ];
    }

    public function reorderModules(array $data): void
    {
        DB::transaction(function () use ($data) {
            $courseId = $data['course_online_id'];
            $offset   = 100000;

            // First pass: move to temporary high order_numbers to avoid unique conflicts
            foreach ($data['modules'] as $item) {
                CourseModule::query()
                    ->where('id', $item['id'])
                    ->where('course_online_id', $courseId)
                    ->update(['order_number' => $item['order_number'] + $offset]);
            }

            // Second pass: set final order_numbers
            foreach ($data['modules'] as $item) {
                CourseModule::query()
                    ->where('id', $item['id'])
                    ->where('course_online_id', $courseId)
                    ->update(['order_number' => $item['order_number']]);
            }
        });
    }

    public function getAdminCourseCards(): array
    {
        $total     = CourseOnline::query()->count();
        $published = CourseOnline::query()->where('status', 'published')->count();
        $draft     = CourseOnline::query()->where('status', 'draft')->count();
        $archived  = CourseOnline::query()->where('status', 'archived')->count();

        return [
            ['key' => 'total_courses',     'title' => 'Total Courses',     'value' => $total],
            ['key' => 'published_courses',  'title' => 'Published Courses', 'value' => $published],
            ['key' => 'draft_courses',      'title' => 'Draft Courses',     'value' => $draft],
            ['key' => 'archived_courses',   'title' => 'Archived Courses',  'value' => $archived],
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
        if (isset($contentData['pdf'])) {
            $pdfMeta = $contentData['pdf'];
            unset($contentData['pdf']);
        }

        $contentData['module_id'] = $moduleId;

        /** @var ModuleContent $content */
        $content = ModuleContent::query()->create($contentData);

        if ($pdfMeta && $content->content_type === 'pdf') {
            ModuleContentPdf::query()->create(array_merge($pdfMeta, [
                'module_content_id' => $content->id,
            ]));
        }

        return $content;
    }

    private function syncModules(CourseOnline $course, array $modules): void
    {
        $existingIds  = $course->modules()->pluck('id')->toArray();
        $incomingIds  = collect($modules)->pluck('id')->filter()->map(fn($v) => (int) $v)->toArray();
        $toDeleteIds  = array_diff($existingIds, $incomingIds);

        foreach ($toDeleteIds as $moduleId) {
            $module = CourseModule::query()->find($moduleId);
            if ($module) {
                $this->deleteModuleWithContent($module);
            }
        }

        foreach ($modules as $moduleData) {
            if (!empty($moduleData['id'])) {
                // Update existing module
                $module = CourseModule::query()->findOrFail($moduleData['id']);
                $contents = null;
                if (array_key_exists('contents', $moduleData)) {
                    $contents = $moduleData['contents'];
                }
                unset($moduleData['contents'], $moduleData['id']);
                $module->update($moduleData);

                if ($contents !== null) {
                    $this->syncContents($module, $contents);
                }
            } else {
                // Create new module
                $this->createModule($course->id, $moduleData);
            }
        }
    }

    private function syncContents(CourseModule $module, array $contents): void
    {
        $existingIds = $module->contents()->pluck('id')->toArray();
        $incomingIds = collect($contents)->pluck('id')->filter()->map(fn($v) => (int) $v)->toArray();
        $toDeleteIds = array_diff($existingIds, $incomingIds);

        foreach ($toDeleteIds as $contentId) {
            $content = ModuleContent::query()->find($contentId);
            if ($content) {
                $this->deleteContentWithPdf($content);
            }
        }

        foreach ($contents as $contentData) {
            if (!empty($contentData['id'])) {
                $content = ModuleContent::query()->findOrFail($contentData['id']);
                $pdfMeta = $contentData['pdf'] ?? null;
                unset($contentData['id'], $contentData['content_type'], $contentData['pdf']); // content_type immutable
                $content->update($contentData);

                if ($pdfMeta && $content->content_type === 'pdf') {
                    $content->pdf()->updateOrCreate(
                        ['module_content_id' => $content->id],
                        $pdfMeta
                    );
                }
            } else {
                $this->createContent($module->id, $contentData);
            }
        }
    }

    private function deleteModuleWithContent(CourseModule $module): void
    {
        foreach ($module->contents as $content) {
            $this->deleteContentWithPdf($content);
        }
        $module->delete();
    }

    private function deleteContentWithPdf(ModuleContent $content): void
    {
        if ($content->content_type === 'pdf') {
            $pdf = $content->pdf;
            if ($pdf) {
                Storage::disk('local')->delete($pdf->file_path);
                $pdf->delete();
            }
        }
        $content->delete();
    }

    private function refreshAnalytics(CourseOnline $course): void
    {
        $course->loadCount('modules');
        $moduleIds     = $course->modules()->pluck('id');
        $totalContents = ModuleContent::query()->whereIn('module_id', $moduleIds)->count();

        $course->analytics()->updateOrCreate(
            ['course_online_id' => $course->id],
            [
                'total_modules'  => $course->modules_count,
                'total_contents' => $totalContents,
            ]
        );
    }
}
