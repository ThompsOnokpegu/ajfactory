<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ship-to-unlock foundation.
 *
 * - enrollments.cohort: which cohort an enrollment belongs to. Defaults to 1,
 *   so every EXISTING student is automatically tagged Cohort 1 (legacy / open
 *   access — no checkpoint gating). The Cohort 2 checkout stamps cohort = 2.
 * - checkpoints: one proof submission per (student, module). The next module
 *   unlocks only when the previous module's checkpoint is approved.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedTinyInteger('cohort')->default(1)->after('plan_type');
        });

        Schema::create('checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('module_id');                       // e.g. 'module-02'
            $table->string('status')->default('submitted');    // submitted | approved | rejected
            $table->string('proof_url')->nullable();           // Loom / Drive / image link
            $table->text('note')->nullable();                  // reviewer note (e.g. rejection reason)
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'module_id']);    // one checkpoint per module per student
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoints');

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('cohort');
        });
    }
};
