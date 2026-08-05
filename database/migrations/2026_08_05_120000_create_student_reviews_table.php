<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staged student feedback — the in-course "soft ask" (see config/reviews.php).
 * One row per (enrollment, stage). A row exists as soon as the student either
 * answers OR declines, so `dismiss_count` is what makes the ask stay soft: we
 * stop asking a stage once they've declined it enough times.
 *
 * `consent_public` is the ONLY flag that makes an answer usable in marketing —
 * it's stored separately from the answers so a quote can never leak into copy
 * just because someone read the JSON.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('stage');                        // config/reviews.php stage key, e.g. 'first-win'
            $table->string('status')->default('dismissed'); // dismissed | submitted
            $table->unsignedTinyInteger('rating')->nullable();
            $table->json('answers')->nullable();            // question key => free text
            $table->boolean('consent_public')->default(false);
            $table->string('credit_as')->nullable();        // full | first | anon
            $table->unsignedTinyInteger('dismiss_count')->default(0);
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'stage']);     // one row per student per stage
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_reviews');
    }
};
