<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {

            // =========================
            // PRIMARY KEY
            // =========================
            $table->uuid('id')->primary();

            // =========================
            // RELATIONSHIPS
            // =========================
            $table->foreignUuid('business_type_id')
                ->nullable()
                ->constrained('business_types')
                ->nullOnDelete();

            // CITY (NEW)
            $table->foreignUuid('city_id')
                ->nullable()
                ->constrained('cities')
                ->nullOnDelete();

            // SUBCITY (NEW)
            $table->foreignUuid('subcity_id')
                ->nullable()
                ->constrained('subcities')
                ->nullOnDelete();

            // WEREDA
            $table->foreignUuid('wereda_id')
                ->nullable()
                ->constrained('weredas')
                ->nullOnDelete();

            $table->foreignUuid('registered_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUuid('owner_id')
                ->nullable()
                ->constrained('owners')
                ->nullOnDelete();

            // =========================
            // BUSINESS IDENTITY
            // =========================
            $table->string('name');
            $table->string('trade_name')->nullable();
            $table->string("description")->nullable();

            // =========================
            // LEGAL IDENTIFIERS
            // =========================
            $table->string('license_number')->nullable()->unique();
            $table->string('tin_number')->nullable()->unique();

            // =========================
            // LOCATION COORDINATES
            // =========================
            $table->decimal('latitude', 10, 7)->nullable()->index();
            $table->decimal('longitude', 10, 7)->nullable()->index();

            // =========================
            // BUSINESS LIFECYCLE STATUS
            // =========================
            $table->enum('status', [
                'active',
                'closed',
            ])->default('active')->index();

            // =========================
            // AUDIT
            // =========================
            $table->timestamps();

            // =========================
            // INDEXES
            // =========================
            $table->index(['city_id']);
            $table->index(['subcity_id']);
            $table->index(['wereda_id']);
            $table->index(['owner_id']);
            $table->index(['business_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};