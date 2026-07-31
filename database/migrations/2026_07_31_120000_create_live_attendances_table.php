<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance for the weekly live sessions. One row per (enrollment, session) —
 * a student marks it by entering the code AJ announces at the end of the live
 * call. Feeds the completion-guarantee progress and unlocks each session's
 * playbook. Mirrors the Checkpoint model's per-enrollment shape.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('live_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('session_key'); // curriculum live session id, e.g. 'live-05'
            $table->timestamp('attended_at');
            $table->timestamps();

            $table->unique(['enrollment_id', 'session_key']); // one mark per session
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_attendances');
    }
};
