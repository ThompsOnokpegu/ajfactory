<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Support\Accelerator;
use Illuminate\Console\Command;

/**
 * Brings existing installment students onto the current due-date rule.
 *
 * Two things drifted: `installment_due_days` was raised from 14 to 21 but the due
 * date is stamped once at checkout, so earlier students kept a 14-day window; and
 * the window used to be counted from the payment date, which penalised anyone who
 * enrolled before the cohort opened.
 *
 * This recomputes from Accelerator::installmentDueAt() and NEVER SHORTENS an
 * existing deadline — a student can only ever gain time here, so re-running it is
 * safe and it can't push anyone into being suddenly overdue.
 *
 * Moving a deadline forward also has to undo what the old one already triggered:
 * `installments:process` flips a student to `link_sent` on the due date and
 * suspends access once the grace period lapses. Left alone, someone would stay
 * locked out against a deadline we've since moved.
 */
class RealignInstallmentDueDates extends Command
{
    protected $signature = 'installments:realign {--dry-run : Show what would change without writing}';

    protected $description = 'Recompute 2nd-installment due dates from the cohort start (never shortens an existing deadline)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $graceHours = (int) config('accelerator.installment_grace_hours', 24);
        $now = now();

        $enrollments = Enrollment::query()
            ->where('plan_type', 'installment')
            ->where('status', 'paid')
            ->where('second_payment_status', '!=', 'paid')
            ->where('balance_due', '>', 0)
            ->get();

        if ($enrollments->isEmpty()) {
            $this->info('No outstanding installment balances — nothing to realign.');

            return self::SUCCESS;
        }

        $rows = [];
        $changed = 0;
        $unsuspended = 0;
        $reset = 0;

        foreach ($enrollments as $enrollment) {
            $anchor = $enrollment->paid_at ?: $enrollment->created_at;
            // Anchored to THIS student's cohort — never the one currently being sold.
            $computed = Accelerator::installmentDueAt($anchor, (int) $enrollment->cohort);
            $current = $enrollment->second_payment_due_at;

            // Only ever extend. A student must not lose time because we recalculated.
            $newDue = ($current && $current->greaterThan($computed)) ? $current : $computed;

            $moves = ! $current || ! $newDue->equalTo($current);
            $updates = [];

            if ($moves) {
                $updates['second_payment_due_at'] = $newDue;
            }

            // The new deadline hasn't arrived yet, so the "pay now" link shouldn't
            // have gone out — let installments:process send it again on the real date.
            $willReset = $newDue->isFuture() && $enrollment->second_payment_status === 'link_sent';
            if ($willReset) {
                $updates['second_payment_status'] = 'pending';
                $updates['installment_reminder_sent_at'] = null;
            }

            // Restore access if the new deadline (plus grace) hasn't passed.
            $willUnsuspend = $enrollment->access_suspended
                && $newDue->copy()->addHours($graceHours)->isFuture();
            if ($willUnsuspend) {
                $updates['access_suspended'] = false;
            }

            if (! $updates) {
                continue;
            }

            $changed++;
            $reset += $willReset ? 1 : 0;
            $unsuspended += $willUnsuspend ? 1 : 0;

            $rows[] = [
                $enrollment->email,
                optional($current)->toDateString() ?: '—',
                $newDue->toDateString(),
                $willReset ? 'yes' : '',
                $willUnsuspend ? 'yes' : '',
            ];

            if (! $dry) {
                $enrollment->update($updates);
            }
        }

        if ($rows) {
            $this->table(['Student', 'Due (was)', 'Due (now)', 'Re-arm link', 'Un-suspend'], $rows);
        }

        $verb = $dry ? 'would change' : 'changed';
        $this->info("{$enrollments->count()} outstanding · {$changed} {$verb} · {$reset} link(s) re-armed · {$unsuspended} un-suspended.");

        if ($dry) {
            $this->comment('Dry run — nothing written. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
