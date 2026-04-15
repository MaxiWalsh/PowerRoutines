<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercise_logs', function (Blueprint $table) {
            // UUID generado en el cliente por sesión de entrenamiento
            // Permite agrupar múltiples logs de la misma sesión
            $table->string('session_id', 36)->nullable()->after('block_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('exercise_logs', function (Blueprint $table) {
            $table->dropColumn('session_id');
        });
    }
};
