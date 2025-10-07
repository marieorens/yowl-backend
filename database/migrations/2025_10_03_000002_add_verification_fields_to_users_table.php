<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('email_verification_token')->nullable()->after('email_verified_at');
            $table->string('password_reset_token')->nullable()->after('password');
            $table->timestamp('password_reset_expires')->nullable()->after('password_reset_token');
        });
        
        // Changer la valeur par défaut de is_active à false
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verified_at',
                'email_verification_token', 
                'password_reset_token',
                'password_reset_expires'
            ]);
            $table->boolean('is_active')->default(true)->change();
        });
    }
};