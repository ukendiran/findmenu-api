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
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->decimal('price', 10, 2);
                $table->string('billing_period')->default('monthly'); // monthly, yearly
                $table->json('features')->nullable();
                $table->integer('trial_days')->default(0);
                $table->json('payment_gateways')->nullable(); // ['phonepe', 'razorpay', 'stripe']
                $table->integer('status')->default(1); // 1=active, 0=inactive
                $table->boolean('is_renewable')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Add new columns if table exists
            Schema::table('subscription_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('subscription_plans', 'trial_days')) {
                    $table->integer('trial_days')->default(0)->after('features');
                }
                if (!Schema::hasColumn('subscription_plans', 'payment_gateways')) {
                    $table->json('payment_gateways')->nullable()->after('trial_days');
                }
                if (!Schema::hasColumn('subscription_plans', 'is_renewable')) {
                    $table->boolean('is_renewable')->default(true)->after('status');
                }
                if (!Schema::hasColumn('subscription_plans', 'description')) {
                    $table->text('description')->nullable()->after('is_renewable');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
