<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add indexes to improve query performance for menu ordering operations.
     * These indexes optimize queries that filter by businessId and order by menuOrderId.
     */
    public function up(): void
    {
        Schema::table('main_categories', function (Blueprint $table) {
            // Index for business-scoped queries ordered by menuOrderId
            $table->index(['businessId', 'menuOrderId'], 'idx_main_categories_business_order');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            // Index for business and category-scoped queries ordered by menuOrderId
            $table->index(['businessId', 'categoryId', 'menuOrderId'], 'idx_sub_categories_business_category_order');
        });

        Schema::table('items', function (Blueprint $table) {
            // Index for business and category-scoped queries ordered by menuOrderId
            $table->index(['businessId', 'categoryId', 'menuOrderId'], 'idx_items_business_category_order');
            
            // Index for business and subcategory-scoped queries ordered by menuOrderId
            $table->index(['businessId', 'subCategoryId', 'menuOrderId'], 'idx_items_business_subcategory_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drop the indexes created in the up() method.
     */
    public function down(): void
    {
        Schema::table('main_categories', function (Blueprint $table) {
            $table->dropIndex('idx_main_categories_business_order');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropIndex('idx_sub_categories_business_category_order');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_business_category_order');
            $table->dropIndex('idx_items_business_subcategory_order');
        });
    }
};
