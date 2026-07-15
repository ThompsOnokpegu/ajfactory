<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store a lead's latest readiness-scorecard outcome on their students row so the
 * admin can see who's landing where (🟢 ready / 🟡 almost / 🔴 not yet) without a
 * separate table. Latest submission wins.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('scorecard_tier')->nullable();          // ready | almost | not_yet
            $table->unsignedTinyInteger('scorecard_score')->nullable(); // 0–100
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['scorecard_tier', 'scorecard_score']);
        });
    }
};
