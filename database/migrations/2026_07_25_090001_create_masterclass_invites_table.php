<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency ledger for the re-invite / "registration is open" announcement
 * (`masterclass:announce`). One row per (email, target session) records that we
 * nudged someone to register, so a re-run — or the daily scheduled tick — never
 * invites the same person to the same session twice. Mirrors the reminder
 * command's stamp-once discipline, but the target here hasn't registered yet, so
 * the stamp can't live on masterclass_registrations.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('masterclass_invites', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('session_date');            // session they were invited to register for
            $table->string('audience')->nullable();    // 'waitlist' | 'past_registrant' — for reporting
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();

            $table->unique(['email', 'session_date']); // one invite per person per session
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masterclass_invites');
    }
};
