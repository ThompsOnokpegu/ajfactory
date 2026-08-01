<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency stamp for the "we're live now" blast (`masterclass:go-live`),
 * sent manually right as the session starts. Separate from the scheduled
 * reminder/day-of/follow-up stamps so it never re-sends.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('masterclass_registrations', function (Blueprint $table) {
            $table->timestamp('live_sent_at')->nullable()->after('dayof_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('masterclass_registrations', function (Blueprint $table) {
            $table->dropColumn('live_sent_at');
        });
    }
};
