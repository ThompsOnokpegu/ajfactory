<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Support\Certificate;
use Illuminate\Console\Command;

/**
 * Issue certificates in bulk to everyone who has already earned one.
 *
 * Not strictly required - the dashboard issues on sight, so a student who logs in gets
 * theirs without anything being run. This exists for the two cases where waiting for a
 * login is wrong:
 *
 *  - Announcing the certificate for the first time. Past finishers (Cohort 2) earned it
 *    before it existed; issuing first means the announcement lands on a certificate that
 *    already exists rather than on a promise.
 *  - Checking who qualifies without opening anyone's dashboard, via --dry-run.
 *
 * Idempotent: an issued certificate keeps its original code and date, because that code
 * is on CVs and in screenshots.
 */
class IssueCertificates extends Command
{
    protected $signature = 'certificates:issue
                            {--dry-run : List who would be issued without writing anything}
                            {--cohort= : Limit to one cohort}';

    protected $description = 'Issue certificates of completion to every student who has passed all core checkpoints';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('DRY RUN - nothing will be written.');
        }

        $enrollments = Enrollment::query()
            ->where('status', 'paid')
            ->when($this->option('cohort'), fn ($q) => $q->where('cohort', (int) $this->option('cohort')))
            ->whereNull('certificate_issued_at')
            ->get();

        if ($enrollments->isEmpty()) {
            $this->info('Every eligible student already has a certificate. Nothing to do.');

            return self::SUCCESS;
        }

        $issued = 0;

        foreach ($enrollments as $enrollment) {
            if (! Certificate::isEarnedBy($enrollment)) {
                continue;
            }

            $name = Certificate::nameFor($enrollment);

            if ($dry) {
                $this->line("  would issue  {$name}  (cohort {$enrollment->cohort})");
                $issued++;
                continue;
            }

            $fresh = Certificate::issueFor($enrollment);
            $this->line("  <fg=green>issued</> {$fresh->certificate_code}  {$name}  (cohort {$fresh->cohort})");
            $issued++;
        }

        $this->newLine();

        if ($issued === 0) {
            $this->info('Nobody has passed every core checkpoint yet.');

            return self::SUCCESS;
        }

        $this->info(($dry ? "Would issue {$issued}" : "Issued {$issued}") . ' certificate(s).');

        if ($dry) {
            $this->warn('Dry run. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
