<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Clear send stamps so `masterclass:remind` will fire a touch again.
 *
 * Needed because a 2xx from the n8n webhook only proves the POST was ACCEPTED —
 * if the webhook responds immediately it can still drop the execution under
 * burst, leaving rows stamped as sent when no email ever went out. This lets you
 * un-stamp the affected people and re-send, without touching the ones who
 * genuinely received it (--except).
 */
class ResetMasterclassSends extends Command
{
    protected $signature = 'masterclass:reset-sends
        {--type=reminder : reminder | dayof | followup}
        {--except= : Comma-separated emails that DID receive it — these keep their stamp}
        {--session= : Session date (defaults to config taab.masterclass.date)}
        {--dry-run : Show who would be reset without writing}';

    protected $description = 'Clear reminder/day-of/follow-up stamps so they can be re-sent';

    private const COLUMNS = [
        'reminder' => 'reminder_sent_at',
        'dayof' => 'dayof_sent_at',
        'followup' => 'followup_sent_at',
    ];

    public function handle(): int
    {
        $type = (string) $this->option('type');
        if (! isset(self::COLUMNS[$type])) {
            $this->error("Unknown --type={$type}. Use: " . implode(', ', array_keys(self::COLUMNS)));
            return self::FAILURE;
        }
        $column = self::COLUMNS[$type];

        $session = $this->option('session') ?: config('taab.masterclass.date');
        if (! $session) {
            $this->error('No session date configured or passed.');
            return self::FAILURE;
        }

        $except = array_filter(array_map(
            fn ($e) => strtolower(trim($e)),
            explode(',', (string) $this->option('except'))
        ));

        $query = DB::table('masterclass_registrations')
            ->where('session_date', $session)
            ->whereNotNull($column);

        if ($except) {
            $query->whereNotIn(DB::raw('LOWER(email)'), $except);
        }

        $rows = (clone $query)->get(['email']);

        if ($rows->isEmpty()) {
            $this->info("Nothing to reset — no stamped '{$type}' rows for {$session}" . ($except ? ' outside --except.' : '.'));
            return self::SUCCESS;
        }

        $this->info(($this->option('dry-run') ? '[dry-run] ' : '')
            . "Clearing '{$type}' for {$rows->count()} registrant(s) on {$session}:");
        foreach ($rows as $r) {
            $this->line('  · ' . $r->email);
        }
        if ($except) {
            $this->comment('Keeping the stamp for ' . count($except) . ' address(es) passed via --except.');
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing written.');
            return self::SUCCESS;
        }

        $cleared = $query->update([$column => null, 'updated_at' => now()]);
        $this->info("Done — {$cleared} cleared. Re-send with: php artisan masterclass:remind --throttle=2000");

        return self::SUCCESS;
    }
}
