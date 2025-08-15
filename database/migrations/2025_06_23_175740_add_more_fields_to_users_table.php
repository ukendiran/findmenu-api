<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreFieldsToUsersTable extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('token')->nullable()->after('password');
            $table->string('mobile', 20)->nullable()->after('token');
            $table->string('phone', 20)->nullable()->after('mobile');
            $table->enum('gender', ['Male', 'Female', 'Other'])->default('Male')->after('phone');
            $table->integer('status')->default(0)->after('gender');
            $table->string('profileImage', 100)->nullable()->after('status');
            $table->string('image', 100)->nullable()->after('profileImage');
            $table->unsignedBigInteger('businessId')->nullable()->after('image');
            $table->softDeletes()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'token',
                'mobile',
                'phone',
                'gender',
                'status',
                'profileImage',
                'image',
                'businessId',
                'deleted_at',
            ]);
        });
    }
}
