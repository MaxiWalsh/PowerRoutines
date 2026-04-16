<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Routine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id', 'name', 'description', 'scope', 'is_template', 'is_active',
        'price', 'is_published', 'marketplace_description',
        'difficulty', 'duration_weeks', 'days_per_week', 'cover_image',
        'discipline', 'target_goals', 'target_level', 'contraindications',
    ];

    protected $casts = [
        'is_template'      => 'boolean',
        'is_active'        => 'boolean',
        'is_published'     => 'boolean',
        'price'            => 'float',
        'target_goals'     => 'array',
        'contraindications'=> 'array',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    /** Rutinas visibles para un user (propias + asignadas activas) */
    public function scopeVisibleTo($query, User $user)
    {
        $gymIds     = $user->gyms()->pluck('gyms.id');
        $profileIds = $user->profiles()->pluck('profiles.id');

        return $query->where(function ($q) use ($user, $gymIds, $profileIds) {
            // Propias — el dueño ve todas (activas e inactivas)
            $q->where('owner_id', $user->id);

            // Asignadas directamente al user (solo activas)
            $q->orWhere(function ($sub) use ($user) {
                $sub->where('is_active', true)
                    ->whereHas('assignments', fn($a) =>
                        $a->where('assignable_type', User::class)
                          ->where('assignable_id', $user->id)
                    );
            });

            // Asignadas al gym del user (solo activas)
            if ($gymIds->isNotEmpty()) {
                $q->orWhere(function ($sub) use ($gymIds) {
                    $sub->where('is_active', true)
                        ->whereHas('assignments', fn($a) =>
                            $a->where('assignable_type', Gym::class)
                              ->whereIn('assignable_id', $gymIds)
                        );
                });
            }

            // Asignadas a un perfil del user (solo activas)
            if ($profileIds->isNotEmpty()) {
                $q->orWhere(function ($sub) use ($profileIds) {
                    $sub->where('is_active', true)
                        ->whereHas('assignments', fn($a) =>
                            $a->where('assignable_type', Profile::class)
                              ->whereIn('assignable_id', $profileIds)
                        );
                });
            }
        });
    }

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Todos los bloques (días + secciones) */
    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('order');
    }

    /** Solo los días (bloques de primer nivel, sin parent) */
    public function days(): HasMany
    {
        return $this->hasMany(Block::class)->whereNull('parent_id')->orderBy('order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RoutineAssignment::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(RoutinePurchase::class);
    }
}
