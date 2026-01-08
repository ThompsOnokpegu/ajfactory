<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->index();
            $table->string('whatsapp')->nullable();
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, paid, failed
            $table->json('paystack_payload')->nullable(); // Store the raw proof
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};