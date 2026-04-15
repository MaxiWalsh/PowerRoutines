<?php

namespace App\Policies;

use App\Models\Gym;
use App\Models\User;

class GymPolicy
{
    /** Solo los trainers pueden crear un gym */
    public function create(User $user): bool
    {
        return $user->isTrainer();
    }

    /** Solo el trainer dueño puede editar */
    public function update(User $user, Gym $gym): bool
    {
        return $user->id === $gym->trainer_id;
    }

    /** Solo el trainer dueño puede ver sus alumnos */
    public function viewStudents(User $user, Gym $gym): bool
    {
        return $user->id === $gym->trainer_id;
    }

    /** Solo el trainer dueño puede borrar */
    public function delete(User $user, Gym $gym): bool
    {
        return $user->id === $gym->trainer_id;
    }
}
