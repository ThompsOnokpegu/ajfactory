<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Run: php artisan make:migration add_progress_to_enrollments_table
public function up() {
    Schema::table('enrollments', function (Blueprint $table) {
        // Stores completed lesson IDs as a JSON array: ["01-01", "01-02"]
        $table->json('completed_lessons')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            //
            $table->dropColumn('completed_lessons');
        });
    }
};
