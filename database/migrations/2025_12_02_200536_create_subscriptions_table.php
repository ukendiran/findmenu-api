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
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('businessId')->constrained('businesses')->onDelete('cascade');
                $table->foreignId('planId')->constrained('subscription_plans')->onDelete('restrict');
                $table->foreignId('paymentId')->nullable()->constrained('payments')->onDelete('set null');
                $table->string('payment_gateway')->nullable();
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->timestamp('trial_ends_at')->nullable();
                $table->boolean('auto_renew')->default(false);
                $table->integer('status')->default(1); // 1=active, 2=expired, 3=cancelled, 4=trial
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['businessId', 'status']);
                $table->index('ends_at');
            });
        } else {
            // Add new columns if table exists
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('subscriptions', 'trial_ends_at')) {
                    $table->timestamp('trial_ends_at')->nullable()->after('ends_at');
                }
                if (!Schema::hasColumn('subscriptions', 'auto_renew')) {
                    $table->boolean('auto_renew')->default(false)->after('trial_ends_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
