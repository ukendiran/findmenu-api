<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('businessId')->nullable();
            $table->unsignedBigInteger('categoryId')->nullable();
            $table->unsignedBigInteger('subCategoryId')->nullable();
            $table->text('image')->nullable();
            $table->text('description')->nullable();
            $table->integer('status')->default(1);
            $table->string('price', 100)->default('0');
            $table->integer('isAvailable')->default(1);
            $table->string('foodType', 10)->nullable();
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->integer('menuOrderId')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('items');
    }
}
