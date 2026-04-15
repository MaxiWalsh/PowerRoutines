<?php

namespace App\Policies;

use App\Models\Routine;
use App\Models\User;

class RoutinePolicy
{
    /** Ver: el dueño o cualquiera que la tenga asignada (directa, gym o perfil) */
    public function view(User $user, Routine $routine): bool
    {
        if ($user->id === $routine->owner_id) return true;

        return $routine->assignments()->where(function ($q) use ($user) {
            // Asignada directamente al user
            $q->where(fn($s) => $s->where('assignable_type', \App\Models\User::class)
                                   ->where('assignable_id', $user->id));

            // Asignada a un gym al que pertenece el user
            $gymIds = $user->gyms()->pluck('gyms.id');
            if ($gymIds->isNotEmpty()) {
                $q->orWhere(fn($s) => $s->where('assignable_type', \App\Models\Gym::class)
                                        ->whereIn('assignable_id', $gymIds));
            }

            // Asignada a un perfil del user
            $profileIds = $user->profiles()->pluck('profiles.id');
            if ($profileIds->isNotEmpty()) {
                $q->orWhere(fn($s) => $s->where('assignable_type', \App\Models\Profile::class)
                                        ->whereIn('assignable_id', $profileIds));
            }
        })->exists();
    }

    /** Editar: solo el dueño */
    public function update(User $user, Routine $routine): bool
    {
        return $user->id === $routine->owner_id;
    }

    /** Borrar: solo el dueño */
    public function delete(User $user, Routine $routine): bool
    {
        return $user->id === $routine->owner_id;
    }

    /** Asignar a otros: solo trainers dueños de la rutina */
    public function assign(User $user, Routine $routine): bool
    {
        return $user->isTrainer() && $user->id === $routine->owner_id;
    }
}
