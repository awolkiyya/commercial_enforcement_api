<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {

            // =========================
            // PRIMARY KEY
            // =========================
            $table->uuid('id')->primary();

            $table->string('inspection_number')->unique();

            // =========================
            // BUSINESS
            // =========================
            $table->foreignUuid('business_id')
                ->constrained('businesses')
                ->cascadeOnDelete();

            // =========================
            // INSPECTOR (CREATOR)
            // =========================
            $table->foreignUuid('inspector_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // =========================
            // AUDIT ACTORS
            // =========================
            $table->foreignUuid('edited_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // =========================
            // TIMELINE (WORKFLOW TRACKING)
            // =========================
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            // =========================
            // STATUS FLOW (UPDATED)
            // =========================
            $table->enum('status', [
                'in_progress',
                'ready_for_resolution',
                'completed',
            ])->default('in_progress');

            // =========================
            // PRIMARY PENALTY (OPTIONAL LINK)
            // =========================
            $table->foreignUuid('penalty_id')
                ->nullable()
                ->constrained('penalties')
                ->nullOnDelete();

            // =========================
            // GENERAL NOTES
            // =========================
            $table->text('notes')->nullable();

            // =========================
            // AUDIT TIMESTAMPS
            // =========================
            $table->timestamps();

            // =========================
            // INDEXES (FOR PERFORMANCE)
            // =========================
            $table->index(['business_id', 'status']);
            $table->index(['inspector_id', 'status']);
            $table->index(['closed_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};