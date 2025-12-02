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
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('business_type_id')->nullable()->after('businessType')->constrained('business_types')->onDelete('set null');
            $table->json('custom_fields')->nullable()->after('business_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['business_type_id']);
            $table->dropColumn(['business_type_id', 'custom_fields']);
        });
    }
};
