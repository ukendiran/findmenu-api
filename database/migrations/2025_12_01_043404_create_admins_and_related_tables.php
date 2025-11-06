<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Table: admins
         */
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->integer('businessId')->default(1);
            $table->string('name', 100);
            $table->string('email', 100);
            $table->string('mobile', 20)->nullable();
            $table->string('password', 250)->nullable();
            $table->string('remember_token', 255)->nullable();
            $table->string('image', 255)->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        /**
         * Table: admin_password_resets
         */
        Schema::create('admin_password_resets', function (Blueprint $table) {
            $table->string('email', 255)->index();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });

        /**
         * Table: admin_personal_access_tokens
         */
        Schema::create('admin_personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type', 255);
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name', 255);
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_personal_access_tokens');
        Schema::dropIfExists('admin_password_resets');
        Schema::dropIfExists('admins');
    }
};
