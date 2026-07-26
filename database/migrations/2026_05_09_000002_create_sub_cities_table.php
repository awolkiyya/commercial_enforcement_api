<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcities', function (Blueprint $table) {

            $table->uuid('id')->primary();

            /**
             * CITY RELATION (BIGINT FK - MATCHES cities.id)
             */
            $table->foreignUuid('city_id')
                ->constrained('cities')
                ->cascadeOnDelete();

            /**
             * CORE DATA
             */
            $table->string('name');

            $table->timestamps();

            /**
             * OPTIONAL: prevent duplicate subcities per city
             */
            $table->unique(['city_id', 'name'], 'unique_subcity_per_city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcities');
    }
};