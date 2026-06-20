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

        // 1. On/after the due date — ask n8n to send the 2nd payment link (once).
        $due = Enrollment::query()
            ->where('plan_type', 'installment')
            ->where('status', 'paid')
            ->where('second_payment_status', 'pending')
            ->where('balance_due', '>', 0)
            ->whereNotNull('second_payment_due_at')
            ->where('second_payment_due_at', '<=', $now)
            ->get();

        foreach ($due as $enrollment) {
            // The signed pay page generates the charge reference itself at pay-time,
            // so the scheduler only flips state + sends the link.
            $enrollment->update([
                'second_payment_status'        => 'link_sent',
                'installment_reminder_sent_at' => $now,
            ]);

            $this->fireWebhook('installment_due', $enrollment);
            $this->info("Payment link sent to {$enrollment->email}");
        }

        // 2. Grace period elapsed and still unpaid — suspend LMS access.
        $overdue = Enrollment::query()
            ->where('plan_type', 'installment')
            ->where('status', 'paid')
            ->where('second_payment_status', '!=', 'paid')
            ->where('balance_due', '>', 0)
            ->where('access_suspended', false)
            ->whereNotNull('second_payment_due_at')
            ->where('second_payment_due_at', '<=', $now->copy()->subHours($graceHours))
            ->get();

        foreach ($overdue as $enrollment) {
            $enrollment->update(['access_suspended' => true]);
            $this->fireWebhook('installment_overdue_suspended', $enrollment);
            $this->warn("Access suspended for {$enrollment->email} (balance {$enrollment->balance_due})");
        }

        $this->info("Installments processed: {$due->count()} link(s) requested, {$overdue->count()} suspension(s).");

        return self::SUCCESS;
    }

    private function fireWebhook(string $event, Enrollment $enrollment): void
    {
        $url = config('services.n8n.installment_webhook') ?: config('services.n8n.enrollment_webhook');

        if (! $url) {
            Log::warning("Installment webhook skipped ({$event}) — no n8n URL configured. Enrollment {$enrollment->id}");
            return;
        }

        // Untamperable link to the hosted "clear your balance" page. No expiry —
        // an overdue student should still be able to pay.
        $payUrl = URL::signedRoute('installment.pay', ['enrollment' => $enrollment->id]);

        try {
            Http::post($url, [
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
            Log::error("Installment n8n trigger failed ({$event}) for {$enrollment->email}: " . $e->getMessage());
        }
    }
}
