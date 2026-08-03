<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Pin to top": a pinned resource floats above the normal sort_order on /free
 * and in the admin list. Multiple pinned items order among themselves by
 * sort_order, same as everything else.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn('is_pinned');
        });
    }
};
