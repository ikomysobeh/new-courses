<?php

namespace App\Console\Commands\LegacyImport;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class LegacyImportCommand extends Command
{
    abstract protected function legacyTable(): string;

    abstract protected function newModel(): string;

    /**
     * Map one legacy row (assoc array) to new-schema attributes.
     * Must include a 'legacy_id' key.
     */
    abstract protected function mapRow(array $old): ?array;

    /**
     * Give subclasses a chance to build lookup maps (e.g. old_id => new_id
     * for a table imported earlier) before rows are mapped.
     */
    protected function beforeImport(): void
    {
    }

    /**
     * Hook for subclasses that need to write to a second table for the same
     * legacy row (e.g. module_content splitting into module_contents +
     * module_content_pdfs). Runs after the main model is saved.
     */
    protected function afterRowSaved(array $old, $model): void
    {
    }

    public function handle(): int
    {
        $this->beforeImport();

        $legacyRows = DB::connection('legacy')->table($this->legacyTable())->get();
        $modelClass = $this->newModel();

        $this->info(sprintf('Importing %d rows from legacy.%s into %s', $legacyRows->count(), $this->legacyTable(), $modelClass));

        $bar = $this->output->createProgressBar($legacyRows->count());
        $skipped = [];

        foreach ($legacyRows as $row) {
            $attributes = $this->mapRow((array) $row);

            if ($attributes === null) {
                $skipped[] = $row->id;
                $bar->advance();

                continue;
            }

            $model = $modelClass::updateOrCreate(['legacy_id' => $attributes['legacy_id']], $attributes);
            $this->afterRowSaved((array) $row, $model);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($skipped !== []) {
            $this->warn(sprintf('Skipped %d row(s) with unresolved mappings: %s', count($skipped), implode(', ', $skipped)));
        }

        $this->verify($legacyRows);

        return self::SUCCESS;
    }

    protected function verify(Collection $legacyRows): void
    {
        $modelClass = $this->newModel();
        $importedCount = $modelClass::whereNotNull('legacy_id')->count();

        $this->line('');
        $this->info('--- Verification ---');
        $this->line("Legacy rows:   {$legacyRows->count()}");
        $this->line("Imported rows: {$importedCount}");

        if ($importedCount !== $legacyRows->count()) {
            $this->warn('Counts do not match - check the skipped list above.');
        }

        $sample = $legacyRows->count() > 3 ? $legacyRows->random(3) : $legacyRows;

        foreach ($sample as $old) {
            $new = $modelClass::where('legacy_id', $old->id)->first();
            $this->line("legacy_id={$old->id}");
            $this->line('  OLD: '.json_encode($old));
            $this->line('  NEW: '.($new ? json_encode($new->toArray()) : 'MISSING'));
        }
    }
}
