<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            // Pre-generate the reference so the eventual charge maps back to this enrollment.
            $reference = 'INST2_' . bin2hex(random_bytes(8));

            $enrollment->update([
                'second_payment_reference'    => $reference,
                'second_payment_status'       => 'link_sent',
                'installment_reminder_sent_at' => $now,
            ]);

            $this->fireWebhook('installment_due', $enrollment, $reference);
            $this->info("Payment link requested for {$enrollment->email} (ref {$reference})");
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
            $this->fireWebhook('installment_overdue_suspended', $enrollment, $enrollment->second_payment_reference);
            $this->warn("Access suspended for {$enrollment->email} (balance {$enrollment->balance_due})");
        }

        $this->info("Installments processed: {$due->count()} link(s) requested, {$overdue->count()} suspension(s).");

        return self::SUCCESS;
    }

    private function fireWebhook(string $event, Enrollment $enrollment, ?string $reference): void
    {
        $url = config('services.n8n.installment_webhook') ?: config('services.n8n.enrollment_webhook');

        if (! $url) {
            Log::warning("Installment webhook skipped ({$event}) — no n8n URL configured. Enrollment {$enrollment->id}");
            return;
        }

        try {
            Http::post($url, [
                'event'              => $event,
                'full_name'          => $enrollment->full_name,
                'email'              => $enrollment->email,
                'phone'              => $enrollment->whatsapp,
                'amount'             => $enrollment->balance_due, // remaining balance to collect
                'currency'           => $enrollment->currency,
                'reference'          => $reference,                // use this as the 2nd charge reference
                'original_reference' => $enrollment->payment_reference,
                'plan_type'          => $enrollment->plan_type,
                'due_at'             => optional($enrollment->second_payment_due_at)->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Installment n8n trigger failed ({$event}) for {$enrollment->email}: " . $e->getMessage());
        }
    }
}
