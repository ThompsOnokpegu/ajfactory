<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TAAB lead capture: tag which tool a student lead came from, and relax the
 * required whatsapp field so the lead-magnet gate can ask for email + name
 * only (low friction). Additive — existing rows keep their values.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'source')) {
                $table->string('source')->nullable()->after('interest');
            }
            $table->string('whatsapp')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('source');
            // leave whatsapp nullable on rollback to avoid failing on null rows
        });
    }
};
