<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paymentId')->constrained()->onDelete('cascade');
            $table->enum('type', ['payment', 'refund']);
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('rupee');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->string('gateway_transactionId')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
