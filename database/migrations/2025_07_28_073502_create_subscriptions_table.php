<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // Proper foreign key to businesses.id (unsignedBigInteger)
            $table->foreignId('businessId')->constrained('businesses')->cascadeOnDelete();

            $table->foreignId('planId')->constrained()->cascadeOnDelete();

            $table->string('payment_gateway')->default('phonepe');
            $table->string('paymentId')->nullable();

            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
