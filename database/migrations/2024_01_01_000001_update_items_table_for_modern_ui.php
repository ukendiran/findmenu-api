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
        Schema::table('items', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('items', 'unit')) {
                $table->string('unit', 50)->nullable()->after('price');
            }
            
            if (!Schema::hasColumn('items', 'featured')) {
                $table->tinyInteger('featured')->default(0)->after('isAvailable');
            }
            
            // Update existing columns
            if (Schema::hasColumn('items', 'price')) {
                $table->string('price', 100)->nullable()->change();
            }
            
            // Add timestamps if they don't exist
            if (!Schema::hasColumn('items', 'created_at')) {
                $table->timestamps();
            }
            
            // Add indexes for better performance
            $table->index(['businessId', 'isAvailable']);
            $table->index(['businessId', 'featured']);
            $table->index(['businessId', 'status']);
            $table->index(['subCategoryId', 'isAvailable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Remove new columns
            if (Schema::hasColumn('items', 'unit')) {
                $table->dropColumn('unit');
            }
            
            if (Schema::hasColumn('items', 'featured')) {
                $table->dropColumn('featured');
            }
            
            // Remove indexes
            $table->dropIndex(['businessId', 'isAvailable']);
            $table->dropIndex(['businessId', 'featured']);
            $table->dropIndex(['businessId', 'status']);
            $table->dropIndex(['subCategoryId', 'isAvailable']);
        });
    }
};