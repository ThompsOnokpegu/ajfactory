<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificate of completion.
 *
 * Three columns rather than a table: a student has exactly one Accelerator
 * enrollment per cohort and exactly one certificate for it, so a separate table
 * would only add a join.
 *
 *  - certificate_name      how the student wants to be credited. Null means "use
 *                          full_name", so an untouched account still prints correctly.
 *  - certificate_code      the public verification code. Unique, issued once, and
 *                          NEVER regenerated: the code is what a student pastes into a
 *                          CV or hands a client, so it has to keep resolving.
 *  - certificate_issued_at when they crossed the bar. Also the flag for "issued" -
 *                          a code with no timestamp should not exist.
 *
 * Earning it is all core checkpoints approved. Deliberately NOT the completion
 * guarantee, which also wants 4 of 6 live sessions: that is a refund promise, and
 * withholding a certificate from someone who shipped all nine builds but missed live
 * calls would be arbitrary. See App\Support\Certificate.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('certificate_name')->nullable()->after('completed_lessons');
            $table->string('certificate_code', 32)->nullable()->unique()->after('certificate_name');
            $table->timestamp('certificate_issued_at')->nullable()->after('certificate_code');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['certificate_code']);
            $table->dropColumn(['certificate_name', 'certificate_code', 'certificate_issued_at']);
        });
    }
};
