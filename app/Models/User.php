<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name', 'email', 'password',
        'avatar', 'birth_date', 'gender',
        'weight_kg', 'height_cm', 'plan',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date'        => 'date',
        'weight_kg'         => 'float',
        'height_cm'         => 'float',
    ];

    // ── Helpers de plan ──────────────────────────────────────────────────────
    public function isPremium(): bool
    {
        return $this->plan === 'premium';
    }

    // ── Helpers de rol ───────────────────────────────────────────────────────
    public function isTrainer(): bool
    {
        return $this->hasRole('trainer');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    // ── Relaciones ───────────────────────────────────────────────────────────

    /** Gimnasio que administra (si es trainer) */
    public function ownedGym(): HasOne
    {
        return $this->hasOne(Gym::class, 'trainer_id');
    }

    /** Gimnasios a los que pertenece como student */
    public function gyms(): BelongsToMany
    {
        return $this->belongsToMany(Gym::class, 'gym_user')
                    ->withPivot('joined_at');
    }

    /** Perfiles de entrenamiento asignados */
    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'profile_user')
                    ->withTimestamps();
    }

    /** Rutinas que el usuario creó */
    public function routines(): HasMany
    {
        return $this->hasMany(Routine::class, 'owner_id');
    }

    /** Rutinas asignadas a este usuario (como assignable) */
    public function assignedRoutines()
    {
        return $this->morphMany(RoutineAssignment::class, 'assignable');
    }

    /** Logs de entrenamiento */
    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class);
    }

    /** Rutinas compradas en el marketplace */
    public function routinePurchases(): HasMany
    {
        return $this->hasMany(RoutinePurchase::class);
    }
}
