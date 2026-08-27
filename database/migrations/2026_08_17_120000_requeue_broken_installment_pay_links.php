<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time repair for students emailed a pay link that could never work.
 *
 * Every 2nd-installment link sent by installments:process was built in the console,
 * where URL::signedRoute() falls back to config('app.url') - and APP_URL was still
 * Laravel's default, so the links pointed at http://localhost. The signature covers
 * the whole absolute URL, so those links 403 and cannot be repaired.
 *
 * The command also stamped second_payment_status = 'link_sent' BEFORE sending, so
 * those students would never be contacted again (the scheduler only picks up rows
 * still 'pending'), and some were then suspended over a link they could not use.
 *
 * This puts them back in the queue. The next installments:process run sends a working
 * link, and the grace period now runs from that send, so nobody is locked out over a
 * message they never received. Anyone who WAS legitimately notified simply gets the
 * link once more and is re-suspended after grace if they still don't pay - so the
 * blunt reset is self-correcting.
 */
return new class extends Migration {
    public function up(): void
    {
        $requeued = DB::table('enrollments')
            ->where('plan_type', 'installment')
            ->where('second_payment_status', 'link_sent')
            ->where('balance_due', '>', 0)
            ->update([
                'second_payment_status' => 'pending',
                'installment_reminder_sent_at' => null,
                'access_suspended' => false,
            ]);

        if ($requeued > 0) {
            echo "  Requeued {$requeued} installment student(s) for a working pay link.\n";
        }
    }

    public function down(): void
    {
        // Deliberately irreversible: the previous state recorded a send that never
        // arrived, so there is nothing worth restoring.
    }
};
