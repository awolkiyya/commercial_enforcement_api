<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('violation_types', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // 📌 Name of violation type
            $table->string('name');

            // 📝 Detailed explanation
            $table->text('description')->nullable();

            // ⚠️ Severity level for risk classification
            $table->enum('severity_level', [
                'low',
                'medium',
                'high',
                'critical'
            ])->default('medium');

            // 🟢 Active / inactive control
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // ⚡ Indexes for performance (reporting & filtering)
            $table->index(['severity_level', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_types');
    }
};