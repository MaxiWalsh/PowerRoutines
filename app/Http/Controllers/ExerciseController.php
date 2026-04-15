<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExerciseController extends Controller
{
    /**
     * GET /exercises
     * Retorna: ejercicios globales + creados por el usuario + ejercicios presentes
     * en rutinas que el usuario posee o tiene asignadas.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // IDs de rutinas que el usuario posee o tiene asignadas directamente
        $accessibleRoutineIds = Routine::where('owner_id', $user->id)
            ->orWhereHas('assignments', function ($q) use ($user) {
                $q->where('assignable_type', \App\Models\User::class)
                  ->where('assignable_id', $user->id);
            })
            ->pluck('id');

        // IDs de ejercicios usados en esas rutinas (via block_exercises → blocks)
        $usedIds = DB::table('block_exercises')
            ->join('blocks', 'blocks.id', '=', 'block_exercises.block_id')
            ->whereIn('blocks.routine_id', $accessibleRoutineIds)
            ->pluck('block_exercises.exercise_id');

        $exercises = Exercise::where('is_global', true)
            ->orWhere('created_by', $user->id)
            ->orWhereIn('id', $usedIds)
            ->orderBy('name')
            ->get();

        return response()->json(ExerciseResource::collection($exercises));
    }

    /** POST /exercises — cualquier usuario autenticado */
    public function store(Request $request): JsonResponse
    {

        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'description'  => 'nullable|string',
            'muscle_group' => 'nullable|string|max:50',
            'equipment'    => 'nullable|string|max:50',
            'video_url'    => 'nullable|url',
            'photo_url'    => 'nullable|url',
            'is_global'    => 'boolean',
        ]);

        $exercise = Exercise::create(array_merge($data, [
            'created_by' => $request->user()->id,
            'is_global'  => $data['is_global'] ?? false,
        ]));

        return response()->json(new ExerciseResource($exercise), 201);
    }

    /** PUT /exercises/{exercise} — solo el creador */
    public function update(Request $request, Exercise $exercise): JsonResponse
    {
        abort_if($exercise->created_by !== $request->user()->id, 403, 'Solo el creador puede editar este ejercicio.');

        $data = $request->validate([
            'name'         => 'sometimes|string|max:100',
            'description'  => 'nullable|string',
            'muscle_group' => 'nullable|string|max:50',
            'equipment'    => 'nullable|string|max:50',
            'video_url'    => 'nullable|url',
            'photo_url'    => 'nullable|url',
            'is_global'    => 'boolean',
        ]);

        $exercise->update($data);

        return response()->json(new ExerciseResource($exercise));
    }

    /** DELETE /exercises/{exercise} — solo el creador */
    public function destroy(Request $request, Exercise $exercise): JsonResponse
    {
        abort_if($exercise->created_by !== $request->user()->id, 403, 'Solo el creador puede eliminar este ejercicio.');
        abort_if($exercise->is_global, 403, 'Los ejercicios globales no se pueden eliminar.');

        $exercise->delete();

        return response()->json(['message' => 'Ejercicio eliminado.']);
    }
}
