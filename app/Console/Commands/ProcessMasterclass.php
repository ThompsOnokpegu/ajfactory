<?php

namespace App\Console\Commands;

use App\Models\MasterclassRegistration;
use App\Support\Masterclass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessMasterclass extends Command
{
    protected $signature = 'masterclass:remind';
    protected $description = 'Fire the masterclass reminder, day-of nudge, and post-session follow-up to n8n (once each)';

    public function handle(): int
    {
        $session = config('taab.masterclass');
        $startsAt = Masterclass::startsAt();
        $endsAt = Masterclass::endsAt();

        if (! $startsAt) {
            $this->warn('No masterclass starts_at configured — nothing to process.');
            return self::SUCCESS;
        }

        $now = now();
        $sessionDate = $session['date'] ?? null;

        $registrations = MasterclassRegistration::query()
            ->where('session_date', $sessionDate)
            ->get();

        $reminderAt = $startsAt->copy()->subHours((int) ($session['reminder_lead_hours'] ?? 24));
        $dayofAt = $startsAt->copy()->subHours((int) ($session['dayof_lead_hours'] ?? 2));
        $followupAt = ($endsAt ?? $startsAt)->copy()->addHours((int) ($session['followup_after_hours'] ?? 2));

        $reminders = 0;
        $dayofs = 0;
        $followups = 0;

        foreach ($registrations as $reg) {
            // 1. T-minus reminder: the Meet link + WhatsApp group.
            if (! $reg->reminder_sent_at && $now->gte($reminderAt) && $now->lt($startsAt)) {
                if ($this->fire('masterclass_reminder', $reg, [
                    'meet_url' => $session['meet_url'] ?? null,
                    'whatsapp_group_url' => $session['whatsapp_group_url'] ?? null,
                ])) {
                    $reg->update(['reminder_sent_at' => $now]);
                    $reminders++;
                }
            }

            // 2. Day-of "starting soon" nudge.
            if (! $reg->dayof_sent_at && $now->gte($dayofAt) && $now->lt($startsAt)) {
                if ($this->fire('masterclass_starting_soon', $reg, [
                    'meet_url' => $session['meet_url'] ?? null,
                ])) {
                    $reg->update(['dayof_sent_at' => $now]);
                    $dayofs++;
                }
            }

            // 3. Post-session follow-up: push the Accelerator.
            if (! $reg->followup_sent_at && $now->gte($followupAt)) {
                if ($this->fire('masterclass_followup', $reg, [
                    'accelerator_url' => config('taab.accelerator_url'),
                    'recording_url' => $session['recording_url'] ?? null,
                ])) {
                    $reg->update(['followup_sent_at' => $now]);
                    $followups++;
                }
            }
        }

        $this->info("Masterclass processed: {$reminders} reminder(s), {$dayofs} day-of nudge(s), {$followups} follow-up(s).");

        return self::SUCCESS;
    }

    /**
     * Fire one masterclass touch to the shared student webhook. Returns true on
     * success so the caller only stamps the row when delivery was accepted.
     */
    private function fire(string $type, MasterclassRegistration $reg, array $extra = []): bool
    {
        $url = config('services.n8n.student_webhook_url');

        if (! $url) {
            Log::warning("Masterclass webhook skipped ({$type}) — no n8n URL configured. Registration {$reg->id}");
            return false;
        }

        try {
            $response = Http::post($url, array_merge([
                'type' => $type,
                'first_name' => $reg->first_name,
                'last_name' => $reg->last_name,
                'name' => trim($reg->first_name . ' ' . $reg->last_name),
                'email' => $reg->email,
                'whatsapp' => $reg->whatsapp,
                'session_date' => $reg->session_date,
                'session_label' => Masterclass::sessionLabel(),
                'starts_at' => optional(Masterclass::startsAt())->toIso8601String(),
                'timestamp' => now()->toIso8601String(),
            ], $extra));

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Masterclass n8n trigger failed ({$type}) for {$reg->email}: " . $e->getMessage());
            return false;
        }
    }
}
