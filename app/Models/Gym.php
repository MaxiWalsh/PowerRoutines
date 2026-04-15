<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Gym extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trainer_id', 'name', 'description', 'logo', 'invite_code',
    ];

    protected static function booted(): void
    {
        static::creating(function (Gym $gym) {
            // Genera un código de invitación único automáticamente
            $gym->invite_code = strtoupper(Str::random(8));
        });
    }

    // ── Relaciones ───────────────────────────────────────────────────────────

    /** El trainer dueño del gym */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    /** Students miembros del gym */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'gym_user')
                    ->withPivot('joined_at');
    }

    /** Perfiles de entrenamiento creados para este gym */
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    /** Rutinas asignadas a este gym (como assignable) */
    public function assignedRoutines()
    {
        return $this->morphMany(RoutineAssignment::class, 'assignable');
    }
}
