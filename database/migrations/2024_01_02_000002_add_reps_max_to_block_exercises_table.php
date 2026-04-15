<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('block_exercises', function (Blueprint $table) {
            // reps pasa a ser el mínimo (o valor fijo si no hay máximo)
            // reps_max es opcional: si está, se muestra como "8-12 reps"
            $table->unsignedSmallInteger('reps_max')->nullable()->after('reps');
        });
    }

    public function down(): void
    {
        Schema::table('block_exercises', function (Blueprint $table) {
            $table->dropColumn('reps_max');
        });
    }
};
