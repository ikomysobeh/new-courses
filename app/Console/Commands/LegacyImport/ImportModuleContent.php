<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\CourseModule;
use App\Models\ModuleContent;
use App\Models\ModuleContentPdf;
use App\Models\Video;

class ImportModuleContent extends LegacyImportCommand
{
    protected $signature = 'legacy:import-module-content';

    protected $description = 'Import module_content -> module_contents, splitting pdf-type rows into a related module_content_pdfs row (new schema splits what was one table into two). Drops file_size/google_drive_pdf_url/is_active (not in new schema).';

    protected array $moduleMap = [];

    protected array $videoMap = [];

    protected function legacyTable(): string
    {
        return 'module_content';
    }

    protected function newModel(): string
    {
        return ModuleContent::class;
    }

    protected function beforeImport(): void
    {
        $this->moduleMap = CourseModule::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
        $this->videoMap = Video::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    protected function mapRow(array $old): ?array
    {
        $newModuleId = $this->moduleMap[$old['module_id']] ?? null;

        if ($newModuleId === null) {
            $this->error("No imported CourseModule for legacy module_id={$old['module_id']} (content legacy_id={$old['id']})");

            return null;
        }

        $newVideoId = null;

        if ($old['content_type'] === 'video') {
            $newVideoId = $this->videoMap[$old['video_id']] ?? null;

            if ($newVideoId === null) {
                $this->error("No imported Video for legacy video_id={$old['video_id']} (content legacy_id={$old['id']})");

                return null;
            }
        }

        return [
            'legacy_id' => $old['id'],
            'module_id' => $newModuleId,
            'content_type' => $old['content_type'],
            'title' => $old['title'],
            'description' => $old['description'],
            'order_number' => $old['order_number'],
            'video_id' => $newVideoId,
            'duration' => $old['duration'],
            'thumbnail_path' => $old['thumbnail_path'],
            'is_required' => $old['is_required'],
            'is_active' => $old['is_active'],
            'attachment_path' => $old['attachment_path'],
            'attachment_name' => $old['attachment_name'],
            'attachment_extension' => $old['attachment_extension'],
            'created_at' => $old['created_at'],
            'updated_at' => $old['updated_at'],
        ];
    }

    protected function afterRowSaved(array $old, $model): void
    {
        if ($old['content_type'] !== 'pdf') {
            return;
        }

        ModuleContentPdf::updateOrCreate(
            ['legacy_id' => $old['id']],
            [
                'module_content_id' => $model->id,
                'file_path' => $old['file_path'],
                'pdf_page_count' => $old['pdf_page_count'],
                'created_at' => $old['created_at'],
                'updated_at' => $old['updated_at'],
            ]
        );
    }
}
