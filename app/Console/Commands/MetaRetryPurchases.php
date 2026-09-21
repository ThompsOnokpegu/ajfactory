<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Support\MetaConversions;
use Illuminate\Console\Command;

/**
 * Resend Purchase events that never reached Meta's Conversions API.
 *
 * The webhooks fire the Purchase inline and stamp `meta_purchase_sent_at` only when
 * Meta accepted it, so a paid enrollment with a null stamp is one Meta never saw.
 * Meta only accepts events up to 7 days old, so older ones are reported and left
 * alone - those buyers still reach Meta through the customer-list audience.
 *
 * Runs daily from .github/workflows/meta-sync.yml (Hostinger cron is dead).
 */
class MetaRetryPurchases extends Command
{
    protected $signature = 'meta:retry-purchases
        {--dry-run : List what would be resent without calling Meta}';

    protected $description = 'Resend paid enrollments whose Purchase never reached the Meta Conversions API (7-day window)';

    public function handle(MetaConversions $meta): int
    {
        if (! $meta->isConfigured()) {
            $this->warn('Meta is not configured (META_PIXEL_ID / META_ACCESS_TOKEN) - nothing to do.');

            return self::SUCCESS;
        }

        $since = now()->subDays(7);

        $unsent = Enrollment::query()
            ->where('status', 'paid')
            ->whereNull('meta_purchase_sent_at')
            ->orderBy('paid_at')
            ->get();

        [$due, $expired] = $unsent->partition(fn (Enrollment $e) => $e->paid_at && $e->paid_at->gte($since));

        if ($expired->isNotEmpty()) {
            $this->comment("{$expired->count()} unsent purchase(s) are older than 7 days and can no longer be sent - skipped.");
        }

        if ($due->isEmpty()) {
            $this->info('Nothing to resend - every purchase in the last 7 days reached Meta.');

            return self::SUCCESS;
        }

        $this->info(($this->option('dry-run') ? '[dry-run] ' : '')."Resending {$due->count()} purchase(s) to Meta:");
        foreach ($due as $e) {
            $this->line("  · {$e->payment_reference}  {$e->email}  {$e->currency} ".number_format((float) $e->amount)."  paid {$e->paid_at->toDateString()}");
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run - nothing sent.');

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($due as $e) {
            if ($meta->purchase($e)) {
                $sent++;
            }

            if (! app()->runningUnitTests()) {
                usleep(250_000);
            }
        }

        $failed = $due->count() - $sent;
        $this->info("Meta retry: {$sent} sent, {$failed} failed, {$expired->count()} skipped (older than 7 days).");

        if ($failed > 0) {
            $this->warn("{$failed} failed and stayed unstamped - the next run retries them.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
