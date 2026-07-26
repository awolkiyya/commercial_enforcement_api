<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weredas', function (Blueprint $table) {

            $table->uuid('id')->primary();

            /**
             * SUBCITY RELATION (BIGINT FK)
             */
            $table->foreignUuid('subcity_id')
            ->constrained('subcities')
            ->cascadeOnDelete();

            /**
             * CORE DATA
             */
            $table->string('name');

            $table->timestamps();

            /**
             * OPTIONAL: prevent duplicate weredas per subcity
             */
            $table->unique(['subcity_id', 'name'], 'unique_wereda_per_subcity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weredas');
    }
};