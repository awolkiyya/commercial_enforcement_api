<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violations', function (Blueprint $table) {

            // =========================
            // PRIMARY KEY
            // =========================
            $table->uuid('id')->primary();

            // =========================
            // INSPECTION RELATION
            // =========================
            $table->foreignUuid('inspection_id')
                ->nullable()
                ->constrained('inspections')
                ->cascadeOnDelete();

            // =========================
            // BUSINESS RELATION
            // =========================
            $table->foreignUuid('business_id')
                ->constrained('businesses')
                ->cascadeOnDelete();

            // =========================
            // VIOLATION TYPE
            // =========================
            $table->foreignUuid('violation_type_id')
                ->constrained('violation_types')
                ->cascadeOnDelete();

            // =========================
            // INSPECTOR (USER ROLE)
            // =========================
            $table->foreignUuid('inspector_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // =========================
            // DETAILS
            // =========================
            $table->text('description')->nullable();


            // =========================
            // AUDIT
            // =========================
            $table->timestamps();

            // =========================
            // INDEXES (performance critical)
            // =========================
            $table->index(['business_id']);
            $table->index(['inspection_id']);
            $table->index(['violation_type_id']);
            $table->index(['inspector_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violations');
    }
};