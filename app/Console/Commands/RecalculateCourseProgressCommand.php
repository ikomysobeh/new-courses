<?php

namespace App\Console\Commands;

use App\Models\UserCourseProgress;
use App\Services\OnlineCourse\User\ContentProgressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repair for user_course_progress rows that went stale because nothing
 * recalculated them.
 *
 * ContentProgressService::recalculateCourseProgress() is the only writer of
 * status / progress_percentage, and it only ever ran when the user themselves
 * completed a video or PDF. Two things therefore left rows permanently wrong:
 *
 *  1. Passing a required quiz did not trigger it, so a user who finished all
 *     content first and passed the quiz afterwards stayed at 'in_progress'
 *     forever despite being at 100%. QuizGradingService::finalizeScore() now
 *     triggers it, but pre-existing rows stay wrong until this runs.
 *  2. Editing a course's curriculum (adding or deleting content) does not
 *     trigger it for the users already enrolled, so their stored
 *     total/completed counts still describe the old lineup. Re-running the
 *     calculation is currently the only thing that corrects those.
 *
 * Reuses the service rather than a hand-written UPDATE so this can never drift
 * from the live rule. completed_at is derived from real activity timestamps
 * instead of the now() the service would otherwise stamp on a backfill.
 */
class RecalculateCourseProgressCommand extends Command
{
    protected $signature = 'courses:recalculate-progress
                            {--course= : Limit to a single course_online_id}
                            {--user= : Limit to a single user_id}
                            {--skip-legacy : Only touch rows created by this app (legacy_id IS NULL), leaving imported completions alone}
                            {--no-demote : Never take a row out of "completed"; only promote stuck rows}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Recompute user_course_progress status/percentage from required content + required quizzes, and repair rows stuck at in_progress.';

    public function handle(ContentProgressService $progress): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = UserCourseProgress::query()
            ->when($this->option('course'), fn ($q, $id) => $q->where('course_online_id', $id))
            ->when($this->option('user'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($this->option('skip-legacy'), fn ($q) => $q->whereNull('legacy_id'))
            ->orderBy('id')
            ->get(['id', 'user_id', 'course_online_id', 'legacy_id', 'status', 'progress_percentage', 'completed_at']);

        if ($rows->isEmpty()) {
            $this->warn('No user_course_progress rows matched.');

            return self::SUCCESS;
        }

        $this->info(sprintf('%s %d row(s)...', $dryRun ? 'Checking' : 'Recalculating', $rows->count()));

        $bar = $this->output->createProgressBar($rows->count());
        $changed = [];
        $skippedDemotions = 0;

        foreach ($rows as $before) {
            // Dry run executes the real service and rolls back, so what it
            // reports can never drift from what a real run would write.
            DB::beginTransaction();

            try {
                $progress->recalculateCourseProgress(
                    $before->user_id,
                    $before->course_online_id,
                    touchLastAccessed: false,
                );

                $this->backfillCompletedAt($before);

                $after = UserCourseProgress::query()
                    ->where('user_id', $before->user_id)
                    ->where('course_online_id', $before->course_online_id)
                    ->first(['status', 'progress_percentage']);

                // Demotion is a real-world call (it strips a completion a user
                // already earned), so it can be opted out of independently of
                // the promotions this command exists to apply.
                $demoting = $before->status === 'completed' && $after && $after->status !== 'completed';

                ($dryRun || ($demoting && $this->option('no-demote')))
                    ? DB::rollBack()
                    : DB::commit();

                if ($demoting && $this->option('no-demote')) {
                    $skippedDemotions++;

                    $bar->advance();

                    continue;
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            if ($after && ($after->status !== $before->status
                || (float) $after->progress_percentage !== (float) $before->progress_percentage)) {
                $changed[] = [
                    $before->user_id,
                    $before->course_online_id,
                    $before->legacy_id === null ? 'app' : 'legacy',
                    sprintf('%s (%.2f%%)', $before->status, $before->progress_percentage),
                    sprintf('%s (%.2f%%)', $after->status, $after->progress_percentage),
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($skippedDemotions > 0) {
            $this->warn(sprintf(
                '%d row(s) left as "completed" by --no-demote (they no longer satisfy the rule).',
                $skippedDemotions
            ));
        }

        if ($changed === []) {
            $this->info('Nothing to change - every row already matches the calculated value.');

            return self::SUCCESS;
        }

        $this->table(['User', 'Course', 'Source', 'Before', 'After'], $changed);

        $promoted = collect($changed)->filter(fn ($r) => str_starts_with($r[4], 'completed'))->count();
        $demoted = collect($changed)->filter(fn ($r) => str_starts_with($r[3], 'completed'))->count();

        $this->info(sprintf(
            '%d row(s) %s  (%d promoted to completed, %d demoted out of completed).',
            count($changed),
            $dryRun ? 'would change (dry run - nothing written)' : 'updated',
            $promoted,
            $demoted
        ));

        if (! $dryRun) {
            $this->newLine();
            $this->comment('Run `php artisan reporting:refresh` to push these into the reporting snapshot.');
        }

        return self::SUCCESS;
    }

    /**
     * The service stamps completed_at = now() when it first marks a row complete,
     * which would date every backfilled completion to today. Replace it with the
     * user's real last qualifying activity: the later of their final required
     * content completion and their first passing attempt on a required quiz.
     */
    protected function backfillCompletedAt(UserCourseProgress $before): void
    {
        if ($before->completed_at !== null) {
            return;
        }

        $row = UserCourseProgress::query()
            ->where('user_id', $before->user_id)
            ->where('course_online_id', $before->course_online_id)
            ->first();

        if (! $row || $row->status !== 'completed') {
            return;
        }

        $lastContent = DB::table('user_content_progress')
            ->where('user_id', $before->user_id)
            ->where('course_online_id', $before->course_online_id)
            ->where('is_completed', true)
            ->max('completed_at');

        $lastQuiz = DB::table('quiz_attempts as qa')
            ->join('quizzes as q', 'q.id', '=', 'qa.quiz_id')
            ->where('qa.user_id', $before->user_id)
            ->where('q.course_online_id', $before->course_online_id)
            ->where('qa.passed', true)
            ->max('qa.completed_at');

        $derived = collect([$lastContent, $lastQuiz])->filter()->max();

        if ($derived) {
            $row->forceFill(['completed_at' => $derived])->save();
        }
    }
}
