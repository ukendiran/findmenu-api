<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('businessId')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('subscriptionId')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->foreignId('paymentId')->nullable()->constrained('payments')->onDelete('set null');
            $table->string('transaction_type'); // subscription_created, payment_initiated, payment_success, payment_failed, subscription_renewed, subscription_cancelled, trial_started, trial_ended
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->string('gateway')->nullable();
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable(); // Full audit trail
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['businessId', 'transaction_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
