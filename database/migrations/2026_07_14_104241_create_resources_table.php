<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free resources (workflows, cheatsheets, templates) shared on the public /free
 * page. Each is just a link the owner pastes (Drive / GitHub / Notion / etc.),
 * managed from the admin — so new drops need no redeploy.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();   // e.g. "n8n Workflow", "Cheatsheet"
            $table->string('url');                     // download / access link
            $table->string('emoji', 16)->nullable();   // card icon
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
