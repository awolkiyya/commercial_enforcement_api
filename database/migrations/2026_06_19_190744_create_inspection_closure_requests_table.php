<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_closure_requests', function (Blueprint $table) {

            // =========================
            // PRIMARY KEY (UUID)
            // =========================
            $table->uuid('id')->primary();

            // =========================
            // INSPECTION LINK
            // =========================
            $table->foreignUuid('inspection_id')
                ->constrained('inspections')
                ->cascadeOnDelete();

            // =========================
            // REQUESTED BY (INSPECTOR)
            // =========================
            $table->foreignUuid('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // =========================
            // REQUEST DETAILS
            // =========================
            $table->text('message')->nullable();

            // =========================
            // STATUS
            // =========================
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            // =========================
            // REVIEWER (SUPERVISOR)
            // =========================
            $table->foreignUuid('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('review_note')->nullable();

            $table->timestamp('reviewed_at')->nullable();

            // =========================
            // TIMESTAMPS
            // =========================
            $table->timestamps();

            // =========================
            // INDEXES
            // =========================
            $table->index(['inspection_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_closure_requests');
    }
};