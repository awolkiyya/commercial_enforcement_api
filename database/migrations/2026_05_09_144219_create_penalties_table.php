<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalties', function (Blueprint $table) {

            // =========================
            // PRIMARY KEY
            // =========================
            $table->uuid('id')->primary();
        
            // =========================
            // PENALTY TYPE
            // =========================
            $table->foreignUuid('penalty_type_id')
                ->constrained('penalty_types')
                ->restrictOnDelete();
        
            // =========================
            // STATUS (VERY IMPORTANT)
            // =========================
            $table->enum('status', [
                'issued',
                'pending',
                'overdue',
                'escalated',
                'paid',
                'cancelled'
            ])->default('issued');
        
            // =========================
            // FINANCIAL OVERRIDE
            // =========================
            $table->decimal('amount', 10, 2)->nullable();
        
            // =========================
            // ISSUER
            // =========================
            $table->foreignUuid('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        
            // =========================
            // DEADLINE
            // =========================
            $table->date('due_date')->nullable();
        
            // =========================
            // NOTES
            // =========================
            $table->text('notes')->nullable();
        
            // =========================
            // AUDIT
            // =========================
            $table->timestamps();
        
            // =========================
            // SOFT DELETE
            // =========================
            $table->softDeletes();
        
            // =========================
            // INDEXES
            // =========================
            $table->index('status');
            $table->index('issued_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalties');
    }
};