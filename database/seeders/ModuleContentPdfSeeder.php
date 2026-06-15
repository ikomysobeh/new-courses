<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleContentPdfSeeder extends Seeder
{
    public function run(): void
    {
        $modules = DB::table('course_modules')->get();

        if ($modules->isEmpty()) {
            $this->command?->warn('ModuleContentPdfSeeder: no modules found — run CourseOnlineSeeder first.');
            return;
        }

        $contentRows = 0;
        $pdfRows     = 0;

        foreach ($modules as $module) {
            // Check if a PDF content item already exists for this module
            $existingPdfContent = DB::table('module_contents')
                ->where('module_id', $module->id)
                ->where('content_type', 'pdf')
                ->first();

            if ($existingPdfContent) {
                // Ensure the pdf record exists
                if (! DB::table('module_content_pdfs')
                    ->where('module_content_id', $existingPdfContent->id)
                    ->exists()) {
                    DB::table('module_content_pdfs')->insert([
                        'module_content_id' => $existingPdfContent->id,
                        'file_path'         => 'pdfs/module_' . $module->id . '_handout.pdf',
                        'pdf_page_count'    => rand(4, 20),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                    $pdfRows++;
                }
                continue;
            }

            // Determine next order number
            $maxOrder = DB::table('module_contents')
                ->where('module_id', $module->id)
                ->max('order_number') ?? 0;

            $contentId = DB::table('module_contents')->insertGetId([
                'module_id'    => $module->id,
                'content_type' => 'pdf',
                'title'        => $module->name . ' — Reference Guide',
                'description'  => 'Supplementary reading material for ' . $module->name . '.',
                'order_number' => $maxOrder + 1,
                'is_required'  => 0,
                'is_active'    => 1,
                'created_at'   => now()->subDays(30),
                'updated_at'   => now()->subDays(30),
            ]);
            $contentRows++;

            DB::table('module_content_pdfs')->insert([
                'module_content_id' => $contentId,
                'file_path'         => 'pdfs/module_' . $module->id . '_handout.pdf',
                'pdf_page_count'    => rand(4, 20),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            $pdfRows++;
        }

        $this->command?->info("ModuleContentPdfSeeder: {$contentRows} PDF content items, {$pdfRows} PDF records across {$modules->count()} modules.");
    }
}
