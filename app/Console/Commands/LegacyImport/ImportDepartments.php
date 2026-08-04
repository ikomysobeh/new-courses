<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\Department;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Departments are the one table where old and new ids are deliberately the
 * same number (not a coincidence we mapped around - it's the real join key
 * here), so this doesn't extend LegacyImportCommand: it upserts by `id`
 * directly instead of by `legacy_id`. Originally skipped entirely because
 * the two tables matched exactly (47/47) - re-checking mid-migration found
 * the legacy database had since grown to 57 rows AND renamed 8 existing
 * departments in production, so a real importer is needed after all.
 */
class ImportDepartments extends Command
{
    protected $signature = 'legacy:import-departments';

    protected $description = 'Upsert departments by id (old/new ids are the same number by design). Adds new departments, and overwrites name/parent_id for existing ones - production is authoritative. is_active=0 maps to a soft delete (deleted_at); slug is generated since the old schema has none.';

    public function handle(): int
    {
        $oldRows = DB::connection('legacy')->table('departments')->orderBy('id')->get();

        $this->info("Importing {$oldRows->count()} rows from legacy.departments into ".Department::class.' (upsert by id)');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $usedSlugs = [];
        $bar = $this->output->createProgressBar($oldRows->count());

        foreach ($oldRows as $old) {
            $slug = Str::slug($old->name);

            if (isset($usedSlugs[$slug])) {
                $slug = $slug.'-'.$old->id;
            }

            $usedSlugs[$slug] = true;

            Department::query()->upsert([[
                'id' => $old->id,
                'name' => $old->name,
                'slug' => $slug,
                'parent_id' => $old->parent_id,
                'sort_order' => $old->id,
                'created_at' => $old->created_at,
                'updated_at' => $old->updated_at,
                'deleted_at' => (int) $old->is_active === 0 ? now() : null,
            ]], ['id'], ['name', 'slug', 'parent_id', 'sort_order', 'updated_at', 'deleted_at']);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $newCount = Department::count();
        $this->info('--- Verification ---');
        $this->line("Legacy rows: {$oldRows->count()}");
        $this->line("Departments in new_courses now: {$newCount}");

        foreach ($oldRows->random(min(3, $oldRows->count())) as $old) {
            $new = Department::find($old->id);
            $this->line("id={$old->id}");
            $this->line('  OLD: '.json_encode($old));
            $this->line('  NEW: '.($new ? json_encode($new->toArray()) : 'MISSING'));
        }

        return self::SUCCESS;
    }
}
