<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'gym_id', 'name', 'description', 'is_global',
    ];

    protected $casts = ['is_global' => 'boolean'];

    // ── Relaciones ───────────────────────────────────────────────────────────

    /** Gym al que pertenece (null si es global) */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /** Users que tienen este perfil asignado */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'profile_user')
                    ->withTimestamps();
    }

    /** Rutinas asignadas a este perfil (como assignable) */
    public function assignedRoutines()
    {
        return $this->morphMany(RoutineAssignment::class, 'assignable');
    }
}
