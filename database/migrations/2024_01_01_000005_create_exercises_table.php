<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de ejercicios
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('muscle_group')->nullable(); // pecho, espalda, piernas...
            $table->string('equipment')->nullable();     // barra, mancuernas, máquina...
            $table->string('video_url')->nullable();
            $table->boolean('is_global')->default(true); // disponible para todos
            $table->timestamps();
        });

        // Bloques dentro de una rutina (ej: "Día A – Pecho y Tríceps")
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Ejercicios dentro de un bloque (pivot con configuración)
        Schema::create('block_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->integer('sets')->default(3);
            $table->integer('reps')->nullable();        // null si es por tiempo
            $table->integer('duration_sec')->nullable(); // null si es por reps
            $table->integer('rest_sec')->default(60);
            $table->integer('order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['block_id', 'exercise_id', 'order']); // evita duplicados en mismo bloque
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_exercises');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('exercises');
    }
};
