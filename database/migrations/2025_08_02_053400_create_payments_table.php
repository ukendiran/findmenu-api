<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('userId')->constrained()->onDelete('cascade');
            $table->string('planId');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('rupee');
            $table->string('status')->default('pending');
            $table->string('gateway');
            $table->string('gateway_paymentId')->nullable();
            $table->timestamps();

            $table->index('userId');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
