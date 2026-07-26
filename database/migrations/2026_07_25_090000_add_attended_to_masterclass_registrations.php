<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance marker for a masterclass registration. Only ~30–35% of registrants
 * actually show, so recording who attended lets the re-invite flow
 * (`masterclass:announce`) later target no-shows and stop hammering people who
 * attend repeatedly without ever enrolling. Defaults false: unknown history
 * counts as "not attended", which is the safe assumption for re-invites.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('masterclass_registrations', function (Blueprint $table) {
            $table->boolean('attended')->default(false)->after('followup_sent_at');
            $table->timestamp('attended_at')->nullable()->after('attended');
        });
    }

    public function down(): void
    {
        Schema::table('masterclass_registrations', function (Blueprint $table) {
            $table->dropColumn(['attended', 'attended_at']);
        });
    }
};
