<?php

namespace App\Console\Commands;

use App\Support\MetaAudiences;
use Illuminate\Console\Command;

/**
 * Push the customer lists to Meta as Custom Audiences (hashed - see MetaUserData).
 *
 * Creates each audience on the ad account the first time and keeps its id in the
 * Setting store, so the only one-time prerequisite is accepting the Custom Audience
 * Terms for the ad account (the command tells you exactly where when it hits that).
 * Safe to run daily: Meta de-duplicates hashes, so re-sending everyone is a no-op.
 *
 * Runs from .github/workflows/meta-sync.yml (Hostinger cron is dead).
 */
class MetaSyncAudiences extends Command
{
    protected $signature = 'meta:sync-audiences
        {--dry-run : Build and count the lists without calling Meta}
        {--list=buyers,leads : Comma-separated subset of lists to sync}';

    protected $description = 'Sync paid buyers and TAAB registrants to Meta Custom Audiences (hashed)';

    public function handle(MetaAudiences $audiences): int
    {
        if (! $audiences->isConfigured()) {
            $this->warn('Meta is not configured (META_ACCESS_TOKEN / META_AD_ACCOUNT_ID) - nothing to do.');

            return self::SUCCESS;
        }

        $lists = array_filter(array_map('trim', explode(',', (string) $this->option('list'))));
        $unknown = array_diff($lists, array_keys(MetaAudiences::LISTS));
        if ($unknown) {
            $this->error('Unknown list(s): '.implode(', ', $unknown).'. Known: '.implode(', ', array_keys(MetaAudiences::LISTS)).'.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $failed = false;

        foreach ($lists as $list) {
            $built = $audiences->rowsFor($list);
            $this->info(($dry ? '[dry-run] ' : '')."[{$list}] {$built['source']} source rows -> ".count($built['rows'])." people ({$built['duplicates']} duplicate emails, {$built['no_email']} without a valid email)");

            if (count($built['rows']) === 0) {
                $this->comment('  nothing to upload.');

                continue;
            }

            if ($dry) {
                $existing = \App\Models\Setting::get(MetaAudiences::LISTS[$list]['setting']);
                $this->line($existing ? "  audience {$existing} (from Setting)" : '  audience would be created: '.MetaAudiences::LISTS[$list]['name']);

                continue;
            }

            $audience = $audiences->ensureAudience($list);
            if (! $audience['id']) {
                $this->reportError($list, $audience['error']);
                $failed = true;

                continue;
            }
            $this->line("  audience {$audience['id']} (".($audience['created'] ? 'created now' : 'from Setting').')');

            $result = $audiences->upload($audience['id'], $built['rows']);
            if (! $result['ok']) {
                $this->reportError($list, $result['error']);
                $failed = true;

                continue;
            }

            $this->line("  uploaded {$result['received']} in {$result['batches']} batch(es), {$result['invalid']} invalid");
        }

        if ($dry) {
            $this->comment('Dry run - nothing sent.');

            return self::SUCCESS;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function reportError(string $list, ?array $error): void
    {
        if (($error['error_subcode'] ?? null) === MetaAudiences::TERMS_NOT_ACCEPTED) {
            $this->error("[{$list}] Meta has not had the Custom Audience Terms accepted for act_".config('services.meta.ad_account_id')
                .'. Accept them once at '.app(MetaAudiences::class)->termsUrl().' and re-run.');

            return;
        }

        $this->error("[{$list}] Meta rejected the request: ".($error['message'] ?? 'unknown')
            .' (code '.($error['code'] ?? '-').' / subcode '.($error['error_subcode'] ?? '-').')');
    }
}
