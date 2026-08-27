<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ProcessInstallments extends Command
{
    protected $signature = 'installments:process';
    protected $description = 'Fire payment-link reminders for due installment balances and suspend access for overdue ones';

    public function handle(): int
    {
        $now = now();
        $graceHours = (int) config('accelerator.installment_grace_hours', 24);

        // 1. On/after the due date - ask n8n to send the 2nd payment link (once).
        $due = Enrollment::query()
            ->where('plan_type', 'installment')
            ->where('status', 'paid')
            ->where('second_payment_status', 'pending')
            ->where('balance_due', '>', 0)
            ->whereNotNull('second_payment_due_at')
            ->where('second_payment_due_at', '<=', $now)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($due as $enrollment) {
            // Stamp ONLY on a confirmed send. This used to stamp first and fire
            // afterwards while swallowing every error, so a student could be marked
            // "link sent" having received nothing - and the next run would skip them
            // forever, because it only selects rows still 'pending'.
            if (! $this->fireWebhook('installment_due', $enrollment)) {
                $failed++;
                $this->warn("Payment link NOT sent to {$enrollment->email} - left pending, next run retries");
                continue;
            }

            $enrollment->update([
                'second_payment_status'        => 'link_sent',
                'installment_reminder_sent_at' => $now,
            ]);

            $sent++;
            $this->info("Payment link sent to {$enrollment->email}");
        }

        // 2. Grace elapsed and still unpaid - suspend LMS access.
        //
        // Only ever suspend someone we have actually reached: the grace period runs
        // from when the link went out, not from the due date. Suspending on the due
        // date alone locked students out over a payment link they never received.
        $overdue = Enrollment::query()
            ->where('plan_type', 'installment')
            ->where('status', 'paid')
            ->where('second_payment_status', 'link_sent')
            ->where('balance_due', '>', 0)
            ->where('access_suspended', false)
            ->whereNotNull('installment_reminder_sent_at')
            ->where('installment_reminder_sent_at', '<=', $now->copy()->subHours($graceHours))
            ->get();

        foreach ($overdue as $enrollment) {
            $enrollment->update(['access_suspended' => true]);
            $this->fireWebhook('installment_overdue_suspended', $enrollment);
            $this->warn("Access suspended for {$enrollment->email} (balance {$enrollment->balance_due})");
        }

        $this->info("Installments processed: {$sent} link(s) sent, {$failed} failed, {$overdue->count()} suspension(s).");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Build the signed pay link, or null when it would be unusable.
     *
     * From the CLI there is no request to take a host from, so URL::signedRoute()
     * falls back to config('app.url'). If APP_URL is unset in production that is
     * "http://localhost" - and because the signature is an HMAC over the whole
     * absolute URL, the link cannot be repaired by rewriting the host later. Better
     * to send nothing and retry than to email a dead payment link.
     */
    private function payUrlFor(Enrollment $enrollment): ?string
    {
        $payUrl = URL::signedRoute('installment.pay', ['enrollment' => $enrollment->id]);
        $host = parse_url($payUrl, PHP_URL_HOST);

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            Log::error(
                "Installment pay link refused: APP_URL resolves to {$host}, so the signed link would 404/403 "
                ."for the student. Set APP_URL in the server .env and run `php artisan config:cache`. "
                ."Enrollment {$enrollment->id}"
            );

            return null;
        }

        return $payUrl;
    }

    /** @return bool true only when n8n genuinely accepted the send. */
    private function fireWebhook(string $event, Enrollment $enrollment): bool
    {
        $url = config('services.n8n.installment_webhook') ?: config('services.n8n.enrollment_webhook');

        if (! $url) {
            Log::warning("Installment webhook skipped ({$event}) - no n8n URL configured. Enrollment {$enrollment->id}");

            return false;
        }

        // Untamperable link to the hosted "clear your balance" page. No expiry -
        // an overdue student should still be able to pay.
        $payUrl = $this->payUrlFor($enrollment);

        if (! $payUrl) {
            return false;
        }

        try {
            $response = Http::post($url, [
                'event'              => $event,
                'full_name'          => $enrollment->full_name,
                'email'              => $enrollment->email,
                'phone'              => $enrollment->whatsapp,
                'amount'             => $enrollment->balance_due, // remaining balance to collect
                'currency'           => $enrollment->currency,
                'pay_url'            => $payUrl,                   // send this to the student
                'original_reference' => $enrollment->payment_reference,
                'plan_type'          => $enrollment->plan_type,
                'due_at'             => optional($enrollment->second_payment_due_at)->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Installment n8n trigger failed ({$event}) for {$enrollment->email}: ".$e->getMessage());

            return false;
        }

        if (! $response->successful()) {
            Log::error(
                "Installment n8n rejected ({$event}) for {$enrollment->email}: HTTP {$response->status()}"
            );

            return false;
        }

        return true;
    }
}
