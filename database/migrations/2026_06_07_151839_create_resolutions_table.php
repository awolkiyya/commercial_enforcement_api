<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolutions', function (Blueprint $table) {

            $table->uuid('id')->primary();
        
            // Inspection relation
            $table->foreignUuid('inspection_id')
                ->constrained('inspections')
                ->cascadeOnDelete();
        
            // Final outcome
            $table->enum('outcome', [
                'closed_case',
                'permanently_closed',
            ])->default('closed_case');
        
            // Decision notes
            $table->text('summary')->nullable();
        
            // Who resolved it
            $table->foreignUuid('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        
            // When resolved
            $table->timestamp('resolved_at')->nullable()->index();
        
            // Optional legal/court document
            $table->string('document_path')->nullable();
        
            $table->timestamps();
        
            // Ensure single final resolution per inspection
            $table->unique('inspection_id');
        
            // Performance index
            $table->index(['inspection_id', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};