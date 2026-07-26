<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {

            // =========================
            // PRIMARY KEY (UUID SYSTEM)
            // =========================
            $table->uuid('id')->primary();

            // =========================
            // INSPECTION RELATION (TARGET OF COMPLAINT)
            // =========================
            $table->foreignUuid('inspection_id')
                ->constrained('inspections')
                ->cascadeOnDelete();

            // =========================
            // COMPLAINT DETAILS
            // =========================
            $table->text('reason');

            // =========================
            // STATUS FLOW (COMPLAINT LIFECYCLE)
            // =========================
            $table->enum('status', [
                'submitted',
                'under_review',
                'approved',   // complaint accepted
                'rejected',   // complaint denied
                'resolved'    // final state after resolution
            ])->default('submitted')->index();

            // =========================
            // REVIEW INFORMATION
            // =========================
            $table->foreignUuid('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->text('decision_notes')->nullable();

            // =========================
            // AUDIT TIMESTAMPS
            // =========================
            $table->timestamps();

            // =========================
            // INDEXES (PERFORMANCE)
            // =========================
            $table->index(['inspection_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};