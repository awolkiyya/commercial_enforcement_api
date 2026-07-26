<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {

            $table->uuid('id')->primary();
        
            /**
             * RELATIONSHIP (IMPORTANT)
             */
            $table->uuidMorphs('fileable');
        
            /**
             * STORAGE
             */
            $table->string('disk')->default('public');
            $table->string('path');
        
            /**
             * FILE INFO
             */
            $table->string('original_name')->nullable();
            $table->string('file_name')->nullable();
        
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
        
            $table->unsignedBigInteger('size')->nullable();
        
            /**
             * CLASSIFICATION
             */
            $table->string('category')->nullable();
        
            /**
             * SECURITY
             */
            $table->enum('visibility', ['public', 'private'])
                ->default('public');
        
            /**
             * FILE HASH
             */
            $table->string('checksum')->nullable();
        
            $table->timestamps();
        
            $table->index('category');
            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};