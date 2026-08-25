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
 *   1. Leads in `students`, selected by source (--sources, default 'waitlist').
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
        {--sources=waitlist : Comma-separated students.source values to invite, e.g. waitlist,accelerator_waitlist,scorecard,roi,tool-stack. Empty string skips selecting by source.}
        {--interests= : Comma-separated students.interest values to invite, e.g. course,community,mentorship. OR-ed with --sources. This is the only way to reach pre-June-2026 leads, whose `source` is NULL.}
        {--past-sessions=2 : How many prior sessions of registrants to re-invite}
        {--limit= : Cap invites sent this run, to stay under daily SMTP limits (default: all). Re-run after the cap resets to send the rest.}
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

        // Pool 1: leads in `students`, selected by source.
        //
        // Defaults to 'waitlist' alone, which is what this command shipped with. The
        // other capture sources — accelerator_waitlist, scorecard, roi, tool-stack —
        // are every bit as invitable, and leaving them out meant the command only ever
        // reached a small slice of the list. Pass --sources to widen it.
        //
        // Filtering on `source` ONLY, deliberately. This used to also require
        // interest='masterclass', which is true for every source='waitlist' row (that
        // pairing is written together by MasterclassController) but false for most
        // others: the accelerator waitlist writes interest='accelerator', the scorecard
        // writes interest='scorecard'. Keeping that clause would have made --sources
        // look like it worked while silently matching nobody.
        $sources = collect(explode(',', (string) $this->option('sources')))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->values();

        // `source` is NULL for every lead captured before that column was added
        // (migration 2026_06_10_000000). Those are the OLDEST rows in the table and a
        // large share of it — and `whereIn('source', …)` can never match NULL, so no
        // value of --sources reaches them. They do carry a meaningful `interest`
        // ('course', 'community', 'mentorship'), so --interests selects on that
        // instead. The two are OR'd: a row matching either is in the audience.
        $interests = collect(explode(',', (string) $this->option('interests')))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->values();

        if ($sources->isNotEmpty() || $interests->isNotEmpty()) {
            // A typo matches zero rows and fails silently, which on a deadline looks
            // identical to "nobody left to invite". Say so instead.
            $this->warnUnknown('Source', $sources, 'source');
            $this->warnUnknown('Interest', $interests, 'interest');

            DB::table('students')
                ->where(function ($q) use ($sources, $interests) {
                    if ($sources->isNotEmpty()) {
                        $q->orWhereIn('source', $sources->all());
                    }
                    if ($interests->isNotEmpty()) {
                        $q->orWhereIn('interest', $interests->all());
                    }
                })
                ->get()
                ->each(function ($s) use (&$audience) {
                    $email = strtolower(trim($s->email));
                    if ($email === '' || isset($audience[$email])) {
                        return;
                    }
                    $audience[$email] = [
                        'name' => $s->name,
                        'whatsapp' => $s->whatsapp,
                        // Record where they actually came from, not a flat 'waitlist',
                        // so the ledger can report which pool converted. Rows matched
                        // on interest have no source, so tag them by interest.
                        'audience' => $s->source ?: ($s->interest ? 'interest:'.$s->interest : 'student'),
                    ];
                });
        }

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
            //
            // An ABANDONED CART IS NOT A BUYER. The checkout writes an enrollment row
            // with status='pending' the moment someone clicks pay — before the payment
            // modal even opens — so this pluck was unfiltered and treated every
            // abandoned cart as a buyer. Those are the hottest leads in the database and
            // they were silently excluded from every invite, permanently, with nothing
            // in any log to show for it.
            //
            // Excluding 'pending' rather than selecting 'paid' on purpose: it names the
            // thing we're correcting, and it fails SAFE. Any unexpected status stays
            // suppressed instead of being mailed a free-funnel invite.
            DB::table('enrollments')->where('status', '!=', 'pending')
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

        // Cap this run to stay under daily SMTP limits. The rest keep their place:
        // they're not stamped, so the next run (after the cap resets) picks them up.
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $eligibleCount = $recipients->count();
        if ($limit !== null) {
            $recipients = $recipients->take($limit);
        }

        $label = ($dryRun ? '[dry-run] ' : '') . "Inviting {$recipients->count()} lead(s) to register for {$session}"
            . ($limit !== null && $eligibleCount > $recipients->count() ? " (of {$eligibleCount} eligible; --limit={$limit})" : '') . ':';
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

        $deferred = $eligibleCount - $recipients->count();
        if ($deferred > 0) {
            $this->warn("{$deferred} eligible lead(s) held back by --limit — re-run after the daily cap resets to send them.");
        }

        return self::SUCCESS;
    }

    /**
     * Warn about any requested value that matches no row in `students`.
     *
     * A misspelled --sources/--interests value simply selects nothing, which is
     * indistinguishable from "everyone has already been invited" — and that is a
     * silent no-op on the one day you needed the send to go out.
     *
     * @param  \Illuminate\Support\Collection<int,string>  $requested
     */
    private function warnUnknown(string $label, $requested, string $column): void
    {
        if ($requested->isEmpty()) {
            return;
        }

        $known = DB::table('students')->whereNotNull($column)
            ->distinct()->pluck($column)->map(fn ($v) => trim((string) $v))->all();

        foreach ($requested->diff($known) as $unknown) {
            $this->warn("{$label} '{$unknown}' matches no rows in `students` — check the spelling. Known: " . implode(', ', $known));
        }
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
                // Relative path (not absolute) so it doesn't depend on APP_URL and the
                // email owns the domain: `https://ajbuildai.com` + `/taab?i=<token>`.
                // The token lets /taab pre-fill what they already gave us.
                'register_url' => route('taab.index', ['i' => $token], false),
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
