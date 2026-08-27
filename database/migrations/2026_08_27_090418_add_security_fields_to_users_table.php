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
        Schema::table('users', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | LOGIN SECURITY
            |--------------------------------------------------------------------------
            |
            | Tracks consecutive failed authentication attempts.
            |
            */

            $table->unsignedSmallInteger('failed_login_attempts')
                ->default(0)
                ->after('is_active');

            /*
            |--------------------------------------------------------------------------
            | TEMPORARY ACCOUNT LOCK
            |--------------------------------------------------------------------------
            |
            | When this timestamp is in the future, authentication
            | attempts should be rejected until the lock expires.
            |
            */

            $table->timestamp('locked_until')
                ->nullable()
                ->index()
                ->after('failed_login_attempts');

            /*
            |--------------------------------------------------------------------------
            | PASSWORD SECURITY
            |--------------------------------------------------------------------------
            |
            | Records when the user's password was last changed.
            | Useful for password expiration, forced password changes,
            | and security auditing.
            |
            */

            $table->timestamp('password_changed_at')
                ->nullable()
                ->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'failed_login_attempts',
                'locked_until',
                'password_changed_at',
            ]);
        });
    }
};