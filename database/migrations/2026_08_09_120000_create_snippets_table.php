<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snippets - reusable text shared with students: AI prompts, code, JSON, config
 * blocks. Lives in the DB rather than config because it gets added weekly and
 * must not need a deploy.
 *
 * `module_id` is a CURRICULUM MODULE ID (a config key like 'module-lead-qualifier'),
 * deliberately NOT a foreign key - the curriculum lives in config/curriculum.php,
 * not a table. NULL means "global": show it on every module.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('snippets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->string('language')->default('text'); // prompt | json | javascript | bash | text
            $table->string('module_id')->nullable()->index(); // null = shown on every module
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snippets');
    }
};
