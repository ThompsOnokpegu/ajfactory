<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tell a student their proof was reviewed, without making them go looking.
 *
 * checkpoints.student_seen_at is when the student last acknowledged the review of
 * THIS checkpoint. A checkpoint counts as unseen news when:
 *
 *     reviewed_at IS NOT NULL AND (student_seen_at IS NULL OR student_seen_at < reviewed_at)
 *
 * Comparing the two timestamps (rather than a boolean flag) means a re-review fires
 * a fresh notice on its own: reject -> resubmit -> approve pushes reviewed_at past
 * the old student_seen_at, so the approval is announced even though the student had
 * already dismissed the rejection.
 *
 * The backfill stamps every ALREADY-reviewed checkpoint as seen. Without it, every
 * student in Cohorts 2 and 3 would log in after this deploy to a pile of banners for
 * approvals they were told about on Telegram weeks ago.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->timestamp('student_seen_at')->nullable()->after('reviewed_at');
        });

        // Existing reviews are old news - mark them seen so nobody gets a backlog.
        DB::table('checkpoints')
            ->whereNotNull('reviewed_at')
            ->update(['student_seen_at' => DB::raw('reviewed_at')]);
    }

    public function down(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->dropColumn('student_seen_at');
        });
    }
};
