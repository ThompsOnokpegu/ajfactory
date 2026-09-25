<?php

namespace App\Console\Commands;

use App\Models\Checkpoint;
use App\Models\Enrollment;
use App\Models\LiveAttendance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Move progress that was written against the wrong enrollment row onto the student's
 * real one.
 *
 * WHY THIS EXISTS: checkout writes a fresh `pending` enrollment on every attempt and
 * the webhook flips only the row matching that payment reference to `paid`. A student
 * who abandoned one checkout therefore has an old pending row sitting in front of
 * their real one. The dashboard used to resolve them with an unfiltered
 * `Enrollment::where('email', ...)->first()`, which returns that older row - so their
 * checkpoints, lesson ticks and live attendance were all attached to a row that the
 * admin progress screen (paid rows only) cannot see. They showed 0/9 having shipped
 * several modules.
 *
 * Enrollment::currentFor() fixes the read path. This command fixes the rows already
 * written. Run it in the same deploy as that fix: until it has run, a repaired read
 * path would show those students zero progress.
 *
 * SAFE TO RE-RUN. It never deletes an enrollment row - pending rows are the
 * abandoned-cart segment the launch playbook sells to, and are not rubbish.
 */
class ReconcileEnrollments extends Command
{
    protected $signature = 'enrollments:reconcile
                            {--dry-run : Show what would move without writing anything}
                            {--email= : Limit to one student}';

    protected $description = 'Move checkpoints, lesson ticks and live attendance onto each student\'s current paid enrollment';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('DRY RUN - nothing will be written.');
        }

        // Emails holding more than one enrollment row are the only ones that can split.
        $emails = Enrollment::query()
            ->when($this->option('email'), fn ($q) => $q->where('email', $this->option('email')))
            ->select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email');

        if ($emails->isEmpty()) {
            $this->info('No student has more than one enrollment row. Nothing to reconcile.');

            return self::SUCCESS;
        }

        $this->line("Found {$emails->count()} student(s) with more than one enrollment row.");
        $this->newLine();

        $touched = 0;
        $movedCheckpoints = 0;
        $movedAttendance = 0;
        $skipped = 0;

        foreach ($emails as $email) {
            $canonical = Enrollment::currentFor($email);

            if (! $canonical) {
                // No paid row at all - just repeat abandoned carts. Nothing to fix, and
                // nothing to merge them INTO.
                $skipped++;
                continue;
            }

            $orphans = Enrollment::where('email', $email)
                ->where('id', '!=', $canonical->id)
                ->get();

            if ($orphans->isEmpty()) {
                continue;
            }

            $orphanIds = $orphans->pluck('id');

            $cps = Checkpoint::whereIn('enrollment_id', $orphanIds)->get();
            $att = LiveAttendance::whereIn('enrollment_id', $orphanIds)->get();
            $lessons = $orphans->flatMap(fn ($o) => is_array($o->completed_lessons) ? $o->completed_lessons : []);

            if ($cps->isEmpty() && $att->isEmpty() && $lessons->isEmpty()) {
                continue;
            }

            $touched++;
            $this->line("<fg=cyan>{$email}</> -> enrollment #{$canonical->id} (cohort {$canonical->cohort})");

            DB::transaction(function () use ($canonical, $cps, $att, $lessons, $dry, &$movedCheckpoints, &$movedAttendance) {
                // --- checkpoints -------------------------------------------------
                // Unique on (enrollment_id, module_id), so a clash has to be resolved
                // rather than reassigned. Approved always wins; otherwise the one
                // reviewed or submitted most recently does.
                $existing = Checkpoint::where('enrollment_id', $canonical->id)->get()->keyBy('module_id');

                foreach ($cps as $cp) {
                    $clash = $existing[$cp->module_id] ?? null;

                    if (! $clash) {
                        $this->line("   move  {$cp->module_id} ({$cp->status})");
                        if (! $dry) {
                            $cp->update(['enrollment_id' => $canonical->id]);
                        }
                        $movedCheckpoints++;
                        continue;
                    }

                    if ($this->beats($cp, $clash)) {
                        $this->line("   move  {$cp->module_id} ({$cp->status}) - replaces {$clash->status} on the current row");
                        if (! $dry) {
                            $clash->delete();
                            $cp->update(['enrollment_id' => $canonical->id]);
                        }
                        $movedCheckpoints++;
                    } else {
                        $this->line("   keep  {$cp->module_id} - current row already has {$clash->status}");
                        if (! $dry) {
                            $cp->delete();
                        }
                    }
                }

                // --- live attendance ---------------------------------------------
                $haveSessions = LiveAttendance::where('enrollment_id', $canonical->id)
                    ->pluck('session_key')
                    ->flip();

                foreach ($att as $row) {
                    if ($haveSessions->has($row->session_key)) {
                        if (! $dry) {
                            $row->delete();
                        }
                        continue;
                    }

                    $this->line("   move  attendance {$row->session_key}");
                    if (! $dry) {
                        $row->update(['enrollment_id' => $canonical->id]);
                    }
                    $movedAttendance++;
                }

                // --- completed lessons -------------------------------------------
                // Self-marked ticks, so a union is the honest merge: a lesson ticked
                // on either row was ticked by that student.
                $merged = collect($canonical->completed_lessons ?? [])
                    ->merge($lessons)
                    ->unique()
                    ->values()
                    ->all();

                if (count($merged) !== count($canonical->completed_lessons ?? [])) {
                    $this->line('   merge ' . count($merged) . ' completed lessons');
                    if (! $dry) {
                        $canonical->update(['completed_lessons' => $merged]);
                    }
                }
            });
        }

        $this->newLine();
        $this->info(($dry ? 'Would move: ' : 'Moved: ')
            . "{$movedCheckpoints} checkpoint(s), {$movedAttendance} attendance row(s) across {$touched} student(s).");

        if ($skipped > 0) {
            $this->line("{$skipped} email(s) had no paid row at all (repeat abandoned carts) - left alone.");
        }

        if ($dry) {
            $this->warn('Dry run. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }

    /** Does the orphan checkpoint represent more progress than the one already there? */
    private function beats(Checkpoint $orphan, Checkpoint $current): bool
    {
        if ($orphan->status === $current->status) {
            return ($orphan->reviewed_at ?? $orphan->submitted_at)
                > ($current->reviewed_at ?? $current->submitted_at);
        }

        $rank = ['approved' => 3, 'submitted' => 2, 'rejected' => 1];

        return ($rank[$orphan->status] ?? 0) > ($rank[$current->status] ?? 0);
    }
}
