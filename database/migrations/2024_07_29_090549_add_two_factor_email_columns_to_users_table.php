<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('email_two_factor_code')
                ->after('password')
                ->nullable();

            $table->timestamp('email_two_factor_code_expires_at')
                ->after('email_two_factor_code')
                ->nullable();
            
            $table->timestamp('email_two_factor_enabled_at')
                ->after('email_two_factor_code_expires_at')
                ->nullable();

            $table->timestamp('email_two_factor_confirmed_at')
                ->after('email_two_factor_enabled_at')
                ->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_two_factor_code',
                'email_two_factor_code_expires_at',
                'email_two_factor_enabled_at',
                'email_two_factor_confirmed_at'
            ]);
        });
    }
};
