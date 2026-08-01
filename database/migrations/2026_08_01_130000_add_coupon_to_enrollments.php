<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records the coupon applied at checkout (if any) and the naira/USD discount it
 * gave, for reconciliation. `amount`/`amount_total` already store the DISCOUNTED
 * figures the buyer was actually charged — these are just the audit trail.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('coupon_code')->nullable()->after('currency');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('coupon_code');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['coupon_code', 'discount_amount']);
        });
    }
};
