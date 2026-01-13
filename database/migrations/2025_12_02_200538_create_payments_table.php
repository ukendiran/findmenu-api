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
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('businessId')->constrained('businesses')->onDelete('cascade');
                $table->foreignId('subscriptionId')->nullable()->constrained('subscriptions')->onDelete('set null');
                $table->foreignId('planId')->constrained('subscription_plans')->onDelete('restrict');
                $table->decimal('amount', 10, 2);
                $table->string('currency', 10)->default('INR');
                $table->string('gateway'); // phonepe, razorpay, stripe
                $table->string('gateway_transaction_id')->nullable();
                $table->string('gateway_payment_id')->nullable();
                $table->string('payment_method')->nullable(); // card, upi, netbanking, etc
                $table->string('status')->default('pending'); // pending, success, failed, refunded
                $table->text('receipt_url')->nullable();
                $table->json('metadata')->nullable(); // Full audit trail
                $table->timestamps();
                
                $table->index(['businessId', 'status']);
                $table->index('gateway_transaction_id');
            });
        } else {
            // Add new columns if table exists
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'subscriptionId')) {
                    $table->foreignId('subscriptionId')->nullable()->after('businessId')->constrained('subscriptions')->onDelete('set null');
                }
                if (!Schema::hasColumn('payments', 'gateway_transaction_id')) {
                    $table->string('gateway_transaction_id')->nullable()->after('gateway');
                }
                if (!Schema::hasColumn('payments', 'gateway_payment_id')) {
                    $table->string('gateway_payment_id')->nullable()->after('gateway_transaction_id');
                }
                if (!Schema::hasColumn('payments', 'payment_method')) {
                    $table->string('payment_method')->nullable()->after('gateway_payment_id');
                }
                if (!Schema::hasColumn('payments', 'receipt_url')) {
                    $table->text('receipt_url')->nullable()->after('status');
                }
                if (!Schema::hasColumn('payments', 'metadata')) {
                    $table->json('metadata')->nullable()->after('receipt_url');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
