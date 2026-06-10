<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registrations for a TAAB masterclass session. Captures the richer event
 * fields (background, goal) the lead-magnet gate doesn't, tagged with the
 * session date so each cohort's sign-ups are distinguishable.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('masterclass_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->index();
            $table->string('whatsapp')->nullable();
            $table->string('background')->nullable();
            $table->string('goal')->nullable();
            $table->string('session_date')->nullable(); // which session they registered for
            $table->string('status')->default('registered');
            $table->timestamps();

            $table->unique(['email', 'session_date']); // one registration per session
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masterclass_registrations');
    }
};
