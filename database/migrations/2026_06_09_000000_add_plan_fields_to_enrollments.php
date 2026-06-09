<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds installment / plan tracking to the enrollments table and fixes the
 * unused `currency` column (it was created as JSON and never made fillable,
 * so currency has never persisted). All additions are defaulted, so existing
 * rows remain valid.
 *
 * NOTE: written but intentionally NOT run automatically — run only after the
 * owner confirms, ideally against a dev DB first.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('plan_type')->default('full')->after('amount');          // full | installment
            $table->decimal('amount_total', 10, 2)->nullable()->after('plan_type');  // total cost of the chosen plan
            $table->decimal('balance_due', 10, 2)->default(0)->after('amount_total'); // outstanding after today's charge
            $table->string('second_payment_status')->default('none')->after('balance_due'); // none | pending | paid
        });

        // Fix the unused `currency` column: was json, holds no data — make it a
        // plain string so the value the checkout sends actually persists.
        if (Schema::hasColumn('enrollments', 'currency')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('currency')->nullable()->default('NGN')->after('second_payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['plan_type', 'amount_total', 'balance_due', 'second_payment_status', 'currency']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->json('currency')->nullable();
        });
    }
};
