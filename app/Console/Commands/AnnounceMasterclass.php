<?php

namespace App\Console\Commands;

use App\Support\Masterclass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Re-invite the funnel to REGISTER for the current session — deliberately not a
 * silent auto-enrol (that's `masterclass:enroll-waitlist`). We nudge two pools to
 * re-register so we capture their fresh goal + a live intent signal:
 *
 *   1. Waitlisters (students.source = 'waitlist') who never registered.
 *   2. Registrants from the last N sessions — most only ever attend once
 *      (~30–35% show rate), so recycling past intent is cheaper than new traffic.
 *
 * Suppressed: anyone already registered for THIS session, anyone who already
 * enrolled in the Accelerator (don't drag a buyer back to the free funnel), and
 * anyone already invited to this session (idempotency ledger: masterclass_invites).
 *
 * Gated on registrationOpen() — no point driving people to a closed form. The
 * n8n side decides channels (email + WhatsApp); we hand it both. --dry-run
 * previews the audience without writing or sending.
 */
class AnnounceMasterclass extends Command
{
    protected $signature = 'masterclass:announce
        {--dry-run : List who would be invited without writing or sending}
        {--past-sessions=2 : How many prior sessions of registrants to re-invite}
        {--throttle= : Override send_throttle_ms (e.g. 2000) if n8n drops sends under burst}';

    protected $description = 'Invite waitlisters + recent past registrants to register for the current masterclass session';

    public function handle(): int
    {
        $session = config('taab.masterclass.date');

        if (! $session) {
            $this->error('No masterclass date configured — nothing to invite to.');
            return self::FAILURE;
        }

        if (! Masterclass::registrationOpen()) {
            $this->warn("Registration for {$session} is not open — skipping announcement.");
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $pastSessions = max(0, (int) $this->option('past-sessions'));

        // --- Build the audience, keyed by lowercased email so the two pools dedupe.
        $audience = [];

        // Pool 1: waitlisters who opted in but never registered.
        DB::table('students')
            ->where('interest', 'masterclass')
            ->where('source', 'waitlist')
            ->get()
            ->each(function ($s) use (&$audience) {
                $email = strtolower(trim($s->email));
                if ($email === '' || isset($audience[$email])) {
                    return;
                }
                $audience[$email] = [
                    'name' => $s->name,
                    'whatsapp' => $s->whatsapp,
                    'audience' => 'waitlist',
                ];
            });

        // Pool 2: registrants from the last N distinct sessions before this one.
        if ($pastSessions > 0) {
            $recentSessions = DB::table('masterclass_registrations')
                ->where('session_date', '<', $session)
                ->whereNotNull('session_date')
                ->select('session_date')->distinct()
                ->orderByDesc('session_date')
                ->limit($pastSessions)
                ->pluck('session_date');

            if ($recentSessions->isNotEmpty()) {
                DB::table('masterclass_registrations')
                    ->whereIn('session_date', $recentSessions)
                    ->get()
                    ->each(function ($r) use (&$audience) {
                        $email = strtolower(trim($r->email));
                        if ($email === '' || isset($audience[$email])) {
                            return; // waitlist entry (or an earlier session row) already claimed them
                        }
                        $audience[$email] = [
                            'name' => trim($r->first_name . ' ' . $r->last_name),
                            'whatsapp' => $r->whatsapp,
                            'audience' => 'past_registrant',
                        ];
                    });
            }
        }

        // --- Suppression sets.
        $suppress = array_flip(array_merge(
            // Already registered for this session.
            DB::table('masterclass_registrations')->where('session_date', $session)
                ->pluck('email')->map(fn ($e) => strtolower(trim($e)))->all(),
            // Already an Accelerator buyer — don't pull them back to the free funnel.
            DB::table('enrollments')
                ->pluck('email')->map(fn ($e) => strtolower(trim($e)))->all(),
            // Already invited to this session — idempotency.
            DB::table('masterclass_invites')->where('session_date', $session)
                ->pluck('email')->map(fn ($e) => strtolower(trim($e)))->all(),
        ));

        $recipients = collect($audience)
            ->reject(fn ($_, $email) => isset($suppress[$email]));

        if ($recipients->isEmpty()) {
            $this->info("Nothing to do — no un-invited, un-registered leads for {$session}.");
            return self::SUCCESS;
        }

        $label = ($dryRun ? '[dry-run] ' : '') . "Inviting {$recipients->count()} lead(s) to register for {$session}:";
        $this->info($label);
        foreach ($recipients as $email => $r) {
            $this->line("  · {$email}" . ($r['name'] ? " ({$r['name']})" : '') . " [{$r['audience']}]");
        }

        if ($dryRun) {
            $this->comment('Dry run — nothing written, nothing sent.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($recipients as $email => $r) {
            // Generate the link token up front so the outgoing URL carries it. On a
            // retry the previous (failed) send never reached anyone, so a fresh
            // token is fine.
            $token = Str::random(48);

            if ($this->fire($email, $r, $session, $token)) {
                DB::table('masterclass_invites')->updateOrInsert(
                    ['email' => $email, 'session_date' => $session],
                    [
                        'name' => $r['name'],
                        'whatsapp' => $r['whatsapp'],
                        'audience' => $r['audience'],
                        'token' => $token,
                        'invited_at' => now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
                $sent++;
            }
        }

        $this->info("Done — {$sent} invite(s) sent and stamped.");

        $failed = $recipients->count() - $sent;
        if ($failed > 0) {
            $this->warn("{$failed} invite(s) failed and stayed unstamped — re-run to retry them.");
        }

        return self::SUCCESS;
    }

    /**
     * Fire one re-invite to the shared student webhook. Returns true only on a
     * genuine 2xx so the caller stamps the ledger only when n8n accepted it —
     * failures stay unstamped and are retried on the next run.
     */
    private function fire(string $email, array $r, string $session, string $token): bool
    {
        $url = config('services.n8n.student_webhook_url');

        if (! $url) {
            Log::warning("Masterclass announce: no n8n URL configured — skipped invite for {$email}.");
            return false;
        }

        [$first] = array_pad(preg_split('/\s+/', trim((string) $r['name']), 2) ?: [], 1, '');

        try {
            $response = Http::timeout(45)->post($url, [
                'type' => 'masterclass_reinvite',
                'name' => $r['name'],
                'first_name' => $first ?: (ucfirst(strtok($email, '@') ?: 'there')),
                'email' => $email,
                'whatsapp' => $r['whatsapp'],
                'audience' => $r['audience'],
                'session_date' => $session,
                'session_label' => Masterclass::sessionLabel(),
                'starts_at' => optional(Masterclass::startsAt())->toIso8601String(),
                // Token lets /taab pre-fill what they already gave us.
                'register_url' => route('taab.index', ['i' => $token]),
                'timestamp' => now()->toIso8601String(),
            ]);

            $ok = $response->successful();

            if (! $ok) {
                Log::error("Masterclass announce rejected for {$email}: HTTP {$response->status()} {$response->body()}");
                $this->warn("  ! {$email} — HTTP {$response->status()} (will retry next run)");
            }
        } catch (\Throwable $e) {
            Log::error("Masterclass announce failed for {$email}: " . $e->getMessage());
            $this->warn("  ! {$email} — {$e->getMessage()} (will retry next run)");
            $ok = false;
        }

        // Same burst-protection as the reminder command.
        $throttleMs = (int) ($this->option('throttle') ?? config('taab.masterclass.send_throttle_ms', 400));
        if ($throttleMs > 0 && ! app()->runningUnitTests()) {
            usleep($throttleMs * 1000);
        }

        return $ok;
    }
}
