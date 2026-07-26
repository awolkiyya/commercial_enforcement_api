<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_market_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // City-level aggregation
            $table->foreignUuid('city_id')
                ->constrained('cities')
                ->cascadeOnDelete();

            $table->foreignUuid('market_item_id')
                ->constrained('market_items')
                ->cascadeOnDelete();

            $table->date('price_date');

            $table->decimal('price', 12, 2);

            $table->string('currency', 10)->default('ETB');

            // price classification
            $table->string('price_type')->default('official');
            // official | retail | wholesale | average

            // data source tracking
            $table->string('source')->nullable();
            // manual | api | scraper | government

            $table->decimal('confidence_score', 5, 2)->nullable();
            // 0.00 - 100.00

            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |---------------------------------------------------------
            | UNIQUE CONSTRAINT (prevents duplicate price entries)
            |---------------------------------------------------------
            */
            $table->unique([
                'city_id',
                'market_item_id',
                'price_date',
                'price_type'
            ]);

            /*
            |---------------------------------------------------------
            | INDEXES (performance optimization)
            |---------------------------------------------------------
            */
            $table->index(['city_id', 'price_date']);
            $table->index(['market_item_id', 'price_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_market_prices');
    }
};