<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: eliminar columnas singulares (pueden tener datos nulos aún)
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['discipline', 'goal']);
        });

        // Paso 2: agregar columnas plurales como JSON arrays
        Schema::table('users', function (Blueprint $table) {
            $table->json('disciplines')->nullable()->after('height_cm'); // ['gym', 'football']
            $table->json('goals')->nullable()->after('disciplines');     // ['strength', 'weight_loss']
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['disciplines', 'goals']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('discipline')->nullable()->after('height_cm');
            $table->string('goal')->nullable()->after('discipline');
        });
    }
};
