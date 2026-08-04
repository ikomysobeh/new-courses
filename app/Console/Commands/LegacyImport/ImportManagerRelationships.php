<?php

namespace App\Console\Commands\LegacyImport;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Doesn't extend LegacyImportCommand: this isn't a 1:1 table copy, it's a
 * many-to-one derivation that updates already-imported `users` rows.
 *
 * Old user_department_roles has 157 rows, but only 73 name a specific
 * subordinate (manages_user_id) - the other 84 are role-only declarations
 * ("this person holds title X in department Y") with no destination in the
 * new schema and are skipped by client decision. Of the 73, "current" means
 * end_date IS NULL (every managed user has at least one such row, so there's
 * no fallback-to-expired-row case). 5 managed users have 2 distinct current
 * managers - both go into the user_manager pivot; the one with the earliest
 * start_date becomes the primary report_to (client decision).
 */
class ImportManagerRelationships extends Command
{
    protected $signature = 'legacy:import-manager-relationships';

    protected $description = 'Derive users.report_to + user_manager pivot from legacy user_department_roles.';

    public function handle(): int
    {
        $userMap = User::query()->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();

        $rows = DB::connection('legacy')
            ->table('user_department_roles')
            ->whereNotNull('manages_user_id')
            ->whereNull('end_date')
            ->get(['user_id', 'manages_user_id', 'start_date']);

        $byManagedUser = $rows->groupBy('manages_user_id');

        $this->info("Deriving manager relationships for {$byManagedUser->count()} managed users from {$rows->count()} current legacy role rows");

        $updated = 0;
        $skipped = [];

        foreach ($byManagedUser as $managedLegacyId => $group) {
            $newManagedUserId = $userMap[$managedLegacyId] ?? null;

            if ($newManagedUserId === null) {
                $skipped[] = $managedLegacyId;

                continue;
            }

            // One earliest start_date per distinct manager (dedupes the same
            // manager appearing twice for different departments).
            $managersByStart = $group
                ->groupBy('user_id')
                ->map(fn ($rowsForManager) => $rowsForManager->min('start_date'))
                ->sort()
                ->keys();

            if ($managersByStart->count() > 2) {
                $this->warn("Managed user legacy_id={$managedLegacyId} has {$managersByStart->count()} distinct current managers - taking the earliest 2 by start_date");
            }

            $managerLegacyIds = $managersByStart->take(2)->values();
            $newManagerIds = $managerLegacyIds
                ->map(fn ($legacyId) => $userMap[$legacyId] ?? null)
                ->filter()
                ->values();

            if ($newManagerIds->isEmpty()) {
                $skipped[] = $managedLegacyId;

                continue;
            }

            $managedUser = User::find($newManagedUserId);
            $managedUser->update(['report_to' => $newManagerIds->first()]);

            if ($newManagerIds->count() > 1) {
                $managedUser->managers()->syncWithoutDetaching([$newManagerIds->get(1)]);
            }

            $updated++;
        }

        if ($skipped !== []) {
            $this->warn(sprintf('Skipped %d managed user(s) with unresolved mappings: %s', count($skipped), implode(', ', $skipped)));
        }

        $this->info('--- Verification ---');
        $this->line("Managed users updated: {$updated}");
        $this->line('Users with report_to set: '.User::whereNotNull('report_to')->count());
        $this->line('user_manager pivot rows: '.DB::table('user_manager')->count());

        foreach (User::whereNotNull('report_to')->inRandomOrder()->limit(3)->get() as $user) {
            $this->line("user={$user->name} (id={$user->id}, legacy_id={$user->legacy_id}) report_to={$user->report_to} managers=".$user->managers()->pluck('users.id')->implode(','));
        }

        return self::SUCCESS;
    }
}
