<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Installment scheduling fields: due date for the 2nd payment, a dedicated
 * reference so the 2nd charge can be matched back to the enrollment, a
 * reminder timestamp (to avoid re-firing the n8n link webhook), and an
 * access_suspended flag for overdue balances. All additive + defaulted.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('second_payment_due_at')->nullable()->after('second_payment_status');
            $table->string('second_payment_reference')->nullable()->after('second_payment_due_at');
            $table->timestamp('installment_reminder_sent_at')->nullable()->after('second_payment_reference');
            $table->boolean('access_suspended')->default(false)->after('installment_reminder_sent_at');

            $table->index('second_payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['second_payment_reference']);
            $table->dropColumn([
                'second_payment_due_at',
                'second_payment_reference',
                'installment_reminder_sent_at',
                'access_suspended',
            ]);
        });
    }
};
