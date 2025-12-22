<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('contact_preference')->default('voice');
            $table->string('estimated_leads_per_month')->nullable();
            $table->string('status')->default('new'); // new, initiated, qualified, junk
            $table->text('factory_notes')->nullable(); // For n8n to write back results
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};