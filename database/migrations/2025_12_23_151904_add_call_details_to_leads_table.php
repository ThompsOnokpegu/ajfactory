<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->longText('transcript')->nullable();
            $table->text('call_summary')->nullable();
            $table->string('recording_url')->nullable();
            $table->integer('call_duration')->nullable(); // in seconds
            $table->json('analysis_data')->nullable(); // structured answers
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Drop the columns added in the up() method
            $table->dropColumn([
                'transcript',
                'call_summary',
                'recording_url',
                'call_duration',
                'analysis_data',
            ]);
        });
    }
};
