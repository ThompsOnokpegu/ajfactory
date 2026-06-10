<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the is_admin flag the app already relies on (CheckEnrollment middleware,
 * the checkpoint review screen) but which was never actually migrated — so
 * `$user->is_admin` has always resolved to null. Defaults to false; flag the
 * owner's account with `php artisan user:admin <email>`.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
