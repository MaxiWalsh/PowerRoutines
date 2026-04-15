<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            // personal: solo el owner | gym: todos del gym | profile: por perfil | student: alumno específico
            $table->enum('scope', ['personal', 'gym', 'profile', 'student'])->default('personal');
            $table->boolean('is_template')->default(false); // plantilla reutilizable
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de asignaciones (Polymorphic: se puede asignar a User, Gym o Profile)
        Schema::create('routine_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete(); // el trainer
            $table->morphs('assignable'); // assignable_type + assignable_id (User | Gym | Profile)
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_assignments');
        Schema::dropIfExists('routines');
    }
};
