<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfileResource;
use App\Models\Gym;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /profiles
     * Globales + los del gym del trainer autenticado (si tiene gym).
     * Students ven globales + los del gym al que pertenecen.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Profile::where('is_global', true);

        // Agregar perfiles del gym propio (trainer) o gyms del student
        $gymIds = $user->isTrainer()
            ? Gym::where('trainer_id', $user->id)->pluck('id')
            : $user->gyms()->pluck('gyms.id');

        if ($gymIds->isNotEmpty()) {
            $query->orWhereIn('gym_id', $gymIds);
        }

        return response()->json(ProfileResource::collection($query->orderBy('name')->get()));
    }

    /**
     * POST /profiles
     * Solo trainers. Si se pasa gym_id debe ser su propio gym.
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->isTrainer(), 403, 'Solo los trainers pueden crear perfiles.');

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
            'gym_id'      => 'nullable|exists:gyms,id',
            'is_global'   => 'boolean',
        ]);

        // Validar que el gym_id pertenece al trainer
        if (!empty($data['gym_id'])) {
            $gym = Gym::findOrFail($data['gym_id']);
            abort_if($gym->trainer_id !== $request->user()->id, 403, 'No sos el dueño de ese gimnasio.');
        }

        $profile = Profile::create($data);

        return response()->json(new ProfileResource($profile), 201);
    }

    /**
     * PUT /profiles/{profile}
     * Solo el trainer dueño del gym al que pertenece el perfil.
     */
    public function update(Request $request, Profile $profile): JsonResponse
    {
        $this->authorizeProfileOwner($request, $profile);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_global'   => 'boolean',
        ]);

        $profile->update($data);

        return response()->json(new ProfileResource($profile));
    }

    /**
     * DELETE /profiles/{profile}
     */
    public function destroy(Request $request, Profile $profile): JsonResponse
    {
        $this->authorizeProfileOwner($request, $profile);

        $profile->delete();

        return response()->json(['message' => 'Perfil eliminado.']);
    }

    /**
     * POST /profiles/{profile}/users/{userId}
     * Trainer asigna un perfil a un student.
     */
    public function assignUser(Request $request, Profile $profile, int $userId): JsonResponse
    {
        abort_unless($request->user()->isTrainer(), 403, 'Solo los trainers pueden asignar perfiles.');

        $student = User::findOrFail($userId);
        abort_unless($student->isStudent(), 422, 'El usuario no es un student.');

        // Evitar duplicados
        if (!$profile->users()->where('user_id', $userId)->exists()) {
            $profile->users()->attach($userId);
        }

        return response()->json([
            'message' => "Perfil '{$profile->name}' asignado a {$student->name}.",
        ]);
    }

    /**
     * DELETE /profiles/{profile}/users/{userId}
     * Trainer quita un perfil de un student.
     */
    public function removeUser(Request $request, Profile $profile, int $userId): JsonResponse
    {
        abort_unless($request->user()->isTrainer(), 403, 'Solo los trainers pueden quitar perfiles.');

        $profile->users()->detach($userId);

        return response()->json(['message' => 'Perfil removido del usuario.']);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function authorizeProfileOwner(Request $request, Profile $profile): void
    {
        $user = $request->user();
        abort_unless($user->isTrainer(), 403, 'Solo los trainers pueden modificar perfiles.');

        if ($profile->gym_id) {
            $gym = Gym::find($profile->gym_id);
            abort_if(!$gym || $gym->trainer_id !== $user->id, 403, 'No tenés permiso para modificar este perfil.');
        } else {
            // Perfil global: solo quien lo creó puede tocarlo (no hay created_by en Profile)
            // Por simplicidad, cualquier trainer puede editar globales
            // Si se quiere restringir, agregar created_by al modelo Profile
        }
    }
}
