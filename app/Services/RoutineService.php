<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\Profile;
use App\Models\Routine;
use App\Models\RoutineAssignment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class RoutineService
{
    public function getForUser(User $user): LengthAwarePaginator
    {
        return Routine::visibleTo($user)
                      ->with(['owner', 'days.sections'])
                      ->paginate(15);
    }

    public function create(User $owner, array $data): Routine
    {
        return Routine::create([
            'owner_id'    => $owner->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'scope'       => $data['scope'] ?? 'personal',
            'is_template' => $data['is_template'] ?? false,
        ]);
    }

    public function update(Routine $routine, array $data): Routine
    {
        $routine->update($data);
        return $routine->fresh(['days.sections.exercises']);
    }

    public function delete(Routine $routine): void
    {
        $routine->delete();
    }

    // ── Asignaciones ─────────────────────────────────────────────────────────

    public function assignToStudent(Routine $routine, User $trainer, int $studentId, array $extra = []): RoutineAssignment
    {
        $student = User::findOrFail($studentId);

        return RoutineAssignment::create([
            'routine_id'      => $routine->id,
            'assigned_by'     => $trainer->id,
            'assignable_type' => User::class,
            'assignable_id'   => $student->id,
            'start_date'      => $extra['start_date'] ?? null,
            'end_date'        => $extra['end_date'] ?? null,
            'notes'           => $extra['notes'] ?? null,
        ]);
    }

    public function assignToGym(Routine $routine, User $trainer, int $gymId, array $extra = []): RoutineAssignment
    {
        $gym = Gym::findOrFail($gymId);

        return RoutineAssignment::create([
            'routine_id'      => $routine->id,
            'assigned_by'     => $trainer->id,
            'assignable_type' => Gym::class,
            'assignable_id'   => $gym->id,
            'start_date'      => $extra['start_date'] ?? null,
            'end_date'        => $extra['end_date'] ?? null,
            'notes'           => $extra['notes'] ?? null,
        ]);
    }

    public function assignToProfile(Routine $routine, User $trainer, int $profileId, array $extra = []): RoutineAssignment
    {
        $profile = Profile::findOrFail($profileId);

        return RoutineAssignment::create([
            'routine_id'      => $routine->id,
            'assigned_by'     => $trainer->id,
            'assignable_type' => Profile::class,
            'assignable_id'   => $profile->id,
            'start_date'      => $extra['start_date'] ?? null,
            'end_date'        => $extra['end_date'] ?? null,
            'notes'           => $extra['notes'] ?? null,
        ]);
    }

    public function removeAssignment(RoutineAssignment $assignment): void
    {
        $assignment->delete();
    }
}
