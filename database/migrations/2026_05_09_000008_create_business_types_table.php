<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_types', function (Blueprint $table) {

            // =========================
            // PRIMARY KEY
            // =========================
            $table->uuid('id')->primary();

            // =========================
            // BUSINESS TYPE INFO
            // =========================
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();

            // =========================
            // CATEGORY (STRING - SIMPLE & FAST)
            // =========================
            $table->string('category');
            // Examples:
            // Fayyaa
            // Daldala
            // Nyaataa fi Dhugaatii
            // Turizimii
            // Oomisha

            // =========================
            // INSPECTION SETTINGS
            // =========================
            $table->unsignedTinyInteger('priority_level')->default(1);

            $table->boolean('is_movable')->default(false);
            $table->boolean('requires_permanent_address')->default(true);
            $table->boolean('requires_inspection')->default(true);

            // inspection frequency in months
            $table->unsignedSmallInteger('inspection_frequency_months')->default(12);

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
            $table->index('code');
            $table->index('category');
            $table->index('priority_level');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_types');
    }
};