<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Meta ads tracking on the enrollment.
 *
 * `meta_context` is captured at checkout ({fbp, fbc, ip, ua, captured_at}) so the
 * server-side Purchase fired later by the payment webhook can be matched back to the
 * browser session that actually bought. `meta_purchase_sent_at` is stamped only when
 * the Conversions API genuinely accepted the event - null means "still to send",
 * which is what meta:retry-purchases keys off. Additive; nothing existing changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->json('meta_context')->nullable()->after('paystack_payload');
            $table->timestamp('meta_purchase_sent_at')->nullable()->after('meta_context');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['meta_context', 'meta_purchase_sent_at']);
        });
    }
};
