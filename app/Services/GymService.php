<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class GymService
{
    public function create(User $trainer, array $data): Gym
    {
        return Gym::create([
            'trainer_id'  => $trainer->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Gym $gym, array $data): Gym
    {
        $gym->update($data);
        return $gym->fresh();
    }

    /**
     * Un student se une al gym usando el invite_code.
     */
    public function joinByCode(User $student, string $inviteCode): Gym
    {
        $gym = Gym::where('invite_code', $inviteCode)->firstOrFail();

        if ($gym->students()->where('user_id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'invite_code' => ['Ya sos miembro de este gimnasio.'],
            ]);
        }

        $gym->students()->attach($student->id, ['joined_at' => now()]);

        return $gym;
    }

    /**
     * El trainer remueve un student de su gym.
     */
    public function removeStudent(Gym $gym, int $studentId): void
    {
        $gym->students()->detach($studentId);
    }

    /**
     * El student abandona el gym por su cuenta.
     */
    public function leaveGym(Gym $gym, User $student): void
    {
        if (! $gym->students()->where('user_id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'gym' => ['No sos miembro de este gimnasio.'],
            ]);
        }
        $gym->students()->detach($student->id);
    }

    public function getStudents(Gym $gym)
    {
        return $gym->students()->with('profiles')->paginate(20);
    }
}
