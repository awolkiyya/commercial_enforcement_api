<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {

            $table->id();
        
            // WHO
            $table->foreignId('user_id')->nullable()->index();
            $table->string('role')->nullable();
            $table->foreignId('sector_id')->nullable()->index();
        
            // WHAT
            $table->enum('type', ['success', 'error', 'warning', 'info'])->index();
            $table->string('module')->index(); // ai | kpi | report | plan | auth
        
            // MESSAGE (short UI text)
            $table->string('message');
        
            // ACTION CONTEXT
            $table->string('action')->nullable();
            $table->string('reference_id')->nullable();
        
            // EXTRA CONTEXT (lightweight JSON)
            $table->json('context')->nullable();
        
            // SECURITY
            $table->string('ip_address')->nullable();
        
            $table->boolean('success')->default(true);
        
            $table->timestamps();
        
            // INDEXES
            $table->index(['type', 'module']);
            $table->index(['user_id', 'type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
