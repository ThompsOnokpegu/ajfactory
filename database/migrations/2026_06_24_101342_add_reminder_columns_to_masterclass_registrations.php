<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency stamps for the automated masterclass touches (reminder, day-of
 * nudge, post-session follow-up). The `masterclass:remind` command sets each
 * once so a touch is never sent twice — mirrors enrollments' reminder stamp.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('masterclass_registrations', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('status');
            $table->timestamp('dayof_sent_at')->nullable()->after('reminder_sent_at');
            $table->timestamp('followup_sent_at')->nullable()->after('dayof_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('masterclass_registrations', function (Blueprint $table) {
            $table->dropColumn(['reminder_sent_at', 'dayof_sent_at', 'followup_sent_at']);
        });
    }
};
