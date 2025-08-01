<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->id();
            $table->json('json')->nullable();
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('businessId')->nullable();
            $table->integer('googleReviewStatus')->default(1);
            $table->text('googleReview')->nullable();
            $table->string('wifiPassword', 20)->nullable();
            $table->integer('wifiPasswordStatus')->default(2);
            $table->integer('instagramStatus')->default(2);
            $table->string('instagram')->nullable();
            $table->integer('review')->default(0);
            $table->integer('reviewStatus')->default(2);
            $table->string('stars', 10)->default('0');
            $table->integer('starsStatus')->default(2);
            $table->integer('googleMapStatus')->default(2);
            $table->text('googleMap')->nullable();
            $table->integer('showFeedbackFormStatus')->default(1);
            $table->integer('facebookStatus')->default(2);
            $table->string('facebook')->nullable();
            $table->integer('youtubeStatus')->default(2);
            $table->string('youtube')->nullable();
            $table->integer('whatsappStatus')->default(2);
            $table->string('whatsapp')->nullable();
            $table->string('tripadvisor')->nullable();
            $table->integer('tripadvisorStatus')->default(2);
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
        Schema::dropIfExists('configs');
    }
}
