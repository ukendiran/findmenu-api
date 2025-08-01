<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('logo', 100)->nullable();
            $table->string('image', 100)->nullable();
            $table->text('bannerImage')->nullable();
            $table->integer('status')->default(1);
            $table->json('social')->nullable();
            $table->text('description')->nullable();
            $table->integer('reviewId')->nullable();
            $table->float('stars')->default(0);
            $table->integer('reviews')->default(0);
            $table->text('map_url')->nullable();
            $table->string('review_url', 255)->nullable();
            $table->string('currency', 20)->default('rupee');
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
        Schema::dropIfExists('businesses');
    }
}
