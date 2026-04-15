<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Block extends Model
{
    use HasFactory;

    protected $fillable = ['routine_id', 'parent_id', 'name', 'order', 'notes'];

    /** Rutina a la que pertenece */
    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    /** Bloque padre (si es una sección dentro de un día) */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'parent_id');
    }

    /** Secciones hijas (si es un día) */
    public function sections(): HasMany
    {
        return $this->hasMany(Block::class, 'parent_id')->orderBy('order');
    }

    /** Ejercicios del bloque (solo aplica a secciones, no a días) */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'block_exercises')
                    ->withPivot(['sets', 'reps', 'reps_max', 'duration_sec', 'rest_sec', 'order', 'notes'])
                    ->orderByPivot('order')
                    ->withTimestamps();
    }
}
