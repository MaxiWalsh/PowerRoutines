<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blocks', function (Blueprint $table) {
            // Permite que un bloque sea "Día" (parent_id null) o "Sección dentro de un día" (parent_id != null)
            $table->foreignId('parent_id')
                  ->nullable()
                  ->after('routine_id')
                  ->references('id')->on('blocks')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blocks', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
