<?php

namespace App\Console\Commands;

use App\Support\Masterclass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Move masterclass waitlisters into the current session's registrations so they
 * enter the reminder flow, then fire a "you're in" confirmation to each. Safe to
 * re-run: anyone already registered for the session is skipped (no duplicate row,
 * no duplicate notice). Use --dry-run to preview who would be enrolled.
 */
class EnrollMasterclassWaitlist extends Command
{
    protected $signature = 'masterclass:enroll-waitlist {--dry-run : List who would be enrolled without writing or notifying} {--source=waitlist : students.source to pull from}';
    protected $description = 'Auto-register masterclass waitlisters for the current session and send each a confirmation';

    public function handle(): int
    {
        $session = config('taab.masterclass.date');
        if (! $session) {
            $this->error('No masterclass date configured — nothing to enroll into.');
            return self::FAILURE;
        }

        $source = (string) $this->option('source');
        $dryRun = (bool) $this->option('dry-run');

        // Emails already registered for this session — never touch them again.
        $registered = DB::table('masterclass_registrations')
            ->where('session_date', $session)
            ->pluck('email')
            ->map(fn ($e) => strtolower(trim($e)))
            ->all();
        $registered = array_flip($registered);

        $waitlisters = DB::table('students')
            ->where('interest', 'masterclass')
            ->where('source', $source)
            ->get();

        $toEnroll = $waitlisters->filter(
            fn ($s) => ! isset($registered[strtolower(trim($s->email))])
        )->values();

        if ($toEnroll->isEmpty()) {
            $this->info("Nothing to do — every '{$source}' masterclass lead is already registered for {$session}.");
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Enrolling {$toEnroll->count()} waitlister(s) into the {$session} session:");
        foreach ($toEnroll as $s) {
            $this->line("  · {$s->email}" . ($s->name ? " ({$s->name})" : ''));
        }

        if ($dryRun) {
            $this->comment('Dry run — no rows written, no confirmations sent.');
            return self::SUCCESS;
        }

        $enrolled = 0;
        $notified = 0;
        foreach ($toEnroll as $s) {
            [$first, $last] = $this->splitName($s->name, $s->email);
            $email = strtolower(trim($s->email));

            DB::table('masterclass_registrations')->updateOrInsert(
                ['email' => $email, 'session_date' => $session],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'whatsapp' => $s->whatsapp,
                    'status' => 'registered',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            // Reclassify the lead so the admin waitlist reflects reality.
            DB::table('students')->where('id', $s->id)->update([
                'source' => 'registration',
                'updated_at' => now(),
            ]);

            $enrolled++;

            if ($this->sendConfirmation($first, $last, $email, $s->whatsapp, $session)) {
                $notified++;
            }
        }

        $this->info("Done — {$enrolled} enrolled, {$notified} confirmation(s) sent.");

        return self::SUCCESS;
    }

    /** First word → first name, remainder → last name; fall back to the email handle. */
    private function splitName(?string $name, string $email): array
    {
        $name = trim((string) $name);
        if ($name === '') {
            $handle = ucfirst(strtok($email, '@') ?: 'there');
            return [$handle, ''];
        }
        $parts = preg_split('/\s+/', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /** Fire the standard registration confirmation to n8n; returns true on success. */
    private function sendConfirmation(string $first, string $last, string $email, ?string $whatsapp, string $session): bool
    {
        $url = config('services.n8n.student_webhook_url');
        if (! $url) {
            Log::warning("Masterclass enroll: no n8n URL configured — skipped confirmation for {$email}.");
            return false;
        }

        try {
            $response = Http::post($url, [
                'type' => 'masterclass_registration',
                'first_name' => $first,
                'last_name' => $last,
                'name' => trim($first . ' ' . $last),
                'email' => $email,
                'whatsapp' => $whatsapp,
                'session_date' => $session,
                'session_label' => Masterclass::sessionLabel(),
                'timestamp' => now()->toIso8601String(),
            ]);
            $ok = $response->successful();
        } catch (\Throwable $e) {
            Log::error("Masterclass enroll confirmation failed for {$email}: " . $e->getMessage());
            $ok = false;
        }

        // Same burst-protection as the reminder command.
        $throttleMs = (int) config('taab.masterclass.send_throttle_ms', 400);
        if ($throttleMs > 0 && ! app()->runningUnitTests()) {
            usleep($throttleMs * 1000);
        }

        return $ok;
    }
}
