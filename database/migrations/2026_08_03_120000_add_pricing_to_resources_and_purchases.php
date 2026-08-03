<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paid resources: a price makes a resource paid (its `url` is only revealed after
 * purchase). resource_purchases records each email-only, one-off buy — verified
 * server-side by the payment webhook before the link is unlocked/delivered.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('url');       // NGN; null/0 = free
            $table->decimal('price_usd', 10, 2)->nullable()->after('price'); // USD; null = not sold in USD
        });

        Schema::create('resource_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->index();
            $table->string('whatsapp')->nullable();
            $table->string('payment_reference')->unique();
            $table->string('access_token', 64)->unique(); // gates the access page (unguessable)
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('pending'); // pending | paid
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_purchases');
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn(['price', 'price_usd']);
        });
    }
};
