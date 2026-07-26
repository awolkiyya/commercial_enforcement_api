<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {

            /**
             * OWNER
             */
            $table->foreignUuid('uploaded_by')
                ->nullable()
                ->after('visibility')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {

            $table->dropForeign(['uploaded_by']);
            $table->dropColumn('uploaded_by');
        });
    }
};