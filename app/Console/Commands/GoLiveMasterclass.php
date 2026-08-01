<?php

namespace App\Console\Commands;

use App\Models\MasterclassRegistration;
use App\Support\Masterclass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * "We're live — join now" blast for the current masterclass session. Run this
 * MANUALLY the moment you go live (e.g. ~5 min before / on the hour): the
 * scheduled 15-min cron can't reliably hit a tight window, so this is a
 * deliberate one-shot. Stamps `live_sent_at` so a re-run only picks up whoever
 * hasn't been sent yet — safe to run again if some failed.
 */
class GoLiveMasterclass extends Command
{
    protected $signature = 'masterclass:go-live
        {--dry-run : List who would be sent without sending}
        {--throttle= : Override send_throttle_ms (e.g. 2000) if n8n drops sends under burst}';

    protected $description = 'Send the "we are live now" nudge (Meet link) to the current session\'s registrants';

    public function handle(): int
    {
        $session = config('taab.masterclass');
        $sessionDate = $session['date'] ?? null;

        if (! $sessionDate) {
            $this->error('No masterclass date configured — nothing to send.');
            return self::FAILURE;
        }

        $pending = MasterclassRegistration::query()
            ->where('session_date', $sessionDate)
            ->whereNull('live_sent_at')
            ->get();

        if ($pending->isEmpty()) {
            $this->info("Nothing to do — every registrant for {$sessionDate} already got the 'we're live' nudge.");
            return self::SUCCESS;
        }

        $this->info(($this->option('dry-run') ? '[dry-run] ' : '') . "Sending 'we're live' to {$pending->count()} registrant(s) for {$sessionDate}:");
        foreach ($pending as $reg) {
            $this->line("  · {$reg->email}");
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing sent.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($pending as $reg) {
            if ($this->fire($reg, ['meet_url' => $session['meet_url'] ?? null])) {
                $reg->update(['live_sent_at' => now()]);
                $sent++;
            }
        }

        $this->info("Done — {$sent} 'we're live' nudge(s) sent.");

        $failed = $pending->count() - $sent;
        if ($failed > 0) {
            $this->warn("{$failed} failed and stayed unstamped — re-run to retry them.");
        }

        return self::SUCCESS;
    }

    /**
     * Fire the live nudge to the shared student webhook. Returns true on a 2xx so
     * the caller only stamps on genuine success (failures retry on the next run).
     */
    private function fire(MasterclassRegistration $reg, array $extra = []): bool
    {
        $url = config('services.n8n.student_webhook_url');

        if (! $url) {
            Log::warning("Masterclass go-live skipped — no n8n URL configured. Registration {$reg->id}");
            return false;
        }

        try {
            $response = Http::timeout(45)->post($url, array_merge([
                'type' => 'masterclass_live',
                'first_name' => $reg->first_name,
                'last_name' => $reg->last_name,
                'name' => trim($reg->first_name . ' ' . $reg->last_name),
                'email' => $reg->email,
                'whatsapp' => $reg->whatsapp,
                'session_date' => $reg->session_date,
                'session_label' => Masterclass::sessionLabel(),
                'timestamp' => now()->toIso8601String(),
            ], $extra));

            $ok = $response->successful();

            if (! $ok) {
                Log::error("Masterclass go-live rejected for {$reg->email}: HTTP {$response->status()} {$response->body()}");
                $this->warn("  ! {$reg->email} — HTTP {$response->status()} (will retry next run)");
            }
        } catch (\Throwable $e) {
            Log::error("Masterclass go-live failed for {$reg->email}: " . $e->getMessage());
            $this->warn("  ! {$reg->email} — {$e->getMessage()} (will retry next run)");
            $ok = false;
        }

        $throttleMs = (int) ($this->option('throttle') ?? config('taab.masterclass.send_throttle_ms', 400));
        if ($throttleMs > 0 && ! app()->runningUnitTests()) {
            usleep($throttleMs * 1000);
        }

        return $ok;
    }
}
