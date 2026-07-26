<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {

            // =========================
            // PRIMARY KEY
            // =========================
            $table->uuid('id')->primary();

            // =========================
            // IDENTITY INFORMATION
            // =========================
            $table->string('full_name');

            $table->string('national_id')
                ->nullable()
                ->unique()
                ->index();

            // =========================
            // CONTACT INFORMATION
            // =========================
            $table->string('phone')
                ->nullable()
                ->unique()
                ->index();

            $table->string('email')
                ->nullable()
                ->index();

            // =========================
            // SYSTEM RELATION
            // =========================
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // =========================
            // STATUS
            // =========================
            $table->boolean('is_active')->default(true);

            // =========================
            // AUDIT
            // =========================
            $table->timestamps();

            // =========================
            // INDEXES
            // =========================
            $table->index(['full_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};