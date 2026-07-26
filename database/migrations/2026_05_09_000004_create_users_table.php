<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        =========================================================
        USERS TABLE (ADAMA ENFORCEMENT SYSTEM)
        =========================================================
        */
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // BASIC AUTH
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable();
            $table->string('password');
            
            $table->foreignUuid('avatar_file_id')
            ->nullable()
            ->constrained('files')
            ->nullOnDelete();

            $table->rememberToken();

            $table->enum('role', [
                'SUPER_ADMIN',
                'ADMIN',
                'SUPERVISOR',
                'INSPECTOR',
            ])->index();


            // LEVEL
            $table->enum('level', [
                'CITY',
                'SUBCITY',
                'WEREDA',
            ])->nullable()->index();


            // STATUS
            $table->boolean('is_active')->default(true);

            $table->timestamp('last_login_at')->nullable()->index();

            $table->timestamps();

            // FOREIGN KEYS
            $table->foreignUuid('city_id')
            ->nullable()
            ->constrained('cities')
            ->nullOnDelete();
        
            $table->foreignUuid('subcity_id')
                ->nullable()
                ->constrained('subcities')
                ->nullOnDelete();
            
            $table->foreignUuid('wereda_id')
                ->nullable()
                ->constrained('weredas')
                ->nullOnDelete();
        });
        /*
        =========================================================
        PASSWORD RESET TOKENS
        =========================================================
        */
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();

            $table->index(['email']);
        });

        /*
        =========================================================
        SESSIONS (OPTIMIZED FOR POSTGRES)
        =========================================================
        */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
        
            $table->uuid('user_id')->nullable();
        
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
        
            $table->longText('payload');
            $table->integer('last_activity')->index();
        
            $table->index('user_id');
        
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};