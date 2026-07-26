<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-invite token carried in the re-invite link (/taab?i=<token>). Lets the
 * registration page recognise a returning lead and pre-fill what they already
 * gave us (name, email, WhatsApp, and — for past registrants — background),
 * without exposing PII in the URL or trusting a tamperable query param.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('masterclass_invites', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('audience');
        });
    }

    public function down(): void
    {
        Schema::table('masterclass_invites', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn('token');
        });
    }
};
