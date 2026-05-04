<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::query()->delete();

        $exportPath = base_path('exports/departments_hierarchy_export.json');

        if (! file_exists($exportPath)) {
            $this->command?->warn('departments_hierarchy_export.json not found. Skipping DepartmentSeeder.');
            return;
        }

        $payload = json_decode(file_get_contents($exportPath), true);
        $flatDepartments = $payload['departments_flat'] ?? [];

        if (! is_array($flatDepartments) || empty($flatDepartments)) {
            $this->command?->warn('No departments found in export file.');
            return;
        }

        $usedSlugs = [];

        foreach ($flatDepartments as $index => $row) {
            if (! isset($row['id'], $row['name'])) {
                continue;
            }

            $baseSlug = Str::slug($row['name']);
            $slug = $baseSlug !== '' ? $baseSlug : 'department';

            // Ensure slug uniqueness even if names are very similar.
            if (in_array($slug, $usedSlugs, true)) {
                $slug .= '-' . $row['id'];
            }

            $usedSlugs[] = $slug;

            Department::query()->updateOrCreate(
                ['id' => (int) $row['id']],
                [
                    'name' => $row['name'],
                    'slug' => $slug,
                    'parent_id' => isset($row['parent_id']) ? (int) $row['parent_id'] : null,
                    'sort_order' => $index + 1,
                    'created_at' => isset($row['created_at']) ? Carbon::parse($row['created_at']) : now(),
                    'updated_at' => isset($row['updated_at']) ? Carbon::parse($row['updated_at']) : now(),
                ]
            );
        }
    }
}
