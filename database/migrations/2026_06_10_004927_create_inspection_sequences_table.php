<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_sequences', function (Blueprint $table) {

            // =========================
            // PRIMARY KEY
            // =========================
            $table->id();

            // =========================
            // ETHIOPIAN YEAR (IMPORTANT)
            // =========================
            $table->string('year', 10)->index();

            // =========================
            // SEQUENCE NUMBER
            // =========================
            $table->unsignedBigInteger('sequence');

            // =========================
            // OPTIONAL: LINK TO INSPECTION
            // =========================
            $table->uuid('inspection_id')->nullable();

            $table->timestamps();

            // =========================
            // UNIQUE RULE (CRITICAL)
            // Each year must have unique sequence
            // =========================
            $table->unique(['year', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_sequences');
    }
};