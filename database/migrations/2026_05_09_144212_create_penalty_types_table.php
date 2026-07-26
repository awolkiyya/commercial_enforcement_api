<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalty_types', function (Blueprint $table) {

            // =========================
            // PRIMARY KEY
            // =========================
            $table->uuid('id')->primary();

            // =========================
            // BASIC INFO
            // =========================
            $table->string('name');
            $table->text('description')->nullable();

            // =========================
            // CLASSIFICATION (ENFORCEMENT LEVELS)
            // =========================
            $table->enum('category', [

                // 🟡 LOW LEVEL ACTIONS
                'warning',

                // 🟠 INSPECTION & CORRECTION
                'monitoring',
                'corrective',

                // 🔵 OPERATIONAL RESTRICTION
                'restriction',
                'suspension',
                'enforcement',

                // 🔴 LEGAL & FINANCIAL
                'fine',
                'legal',

                // ⚫ TERMINATION
                'closure'
            ]);

            // =========================
            // BEHAVIOR RULES (IMPORTANT FOR BUSINESS LOGIC)
            // =========================

            // Whether this penalty requires a due date
            $table->boolean('requires_due_date')->default(true);

            // Whether this action is final (e.g. full closure)
            $table->boolean('is_final_action')->default(false);

            // Whether escalation is allowed from this penalty type
            $table->boolean('allows_escalation')->default(true);

            // Whether inspection continues after this penalty
            $table->boolean('stops_inspection_flow')->default(false);

            // =========================
            // STATUS CONTROL
            // =========================
            $table->boolean('status')->default(true);

            // =========================
            // AUDIT
            // =========================
            $table->timestamps();

            // =========================
            // INDEXES
            // =========================
            $table->index(['category', 'status']);
            $table->index('is_final_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_types');
    }
};