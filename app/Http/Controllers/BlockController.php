<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlockResource;
use App\Models\Block;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    /**
     * POST /routines/{routine}/blocks
     *
     * Sin parent_id → crea un DÍA (bloque de primer nivel).
     * Con parent_id  → crea una SECCIÓN dentro del día.
     */
    public function store(Request $request, Routine $routine): JsonResponse
    {
        $this->authorizeRoutineOwner($request, $routine);

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'order'     => 'integer|min:0',
            'notes'     => 'nullable|string',
            'parent_id' => 'nullable|exists:blocks,id',
        ]);

        // Si se pasa parent_id, verificar que el padre pertenece a esta rutina
        // y que no sea ya una sección (máximo 2 niveles: día → sección)
        if (!empty($data['parent_id'])) {
            $parent = Block::findOrFail($data['parent_id']);
            abort_if($parent->routine_id !== $routine->id, 422, 'El bloque padre no pertenece a esta rutina.');
            abort_if($parent->parent_id !== null, 422, 'No se pueden anidar bloques más de dos niveles.');
        }

        $block = $routine->blocks()->create($data);

        // Devolver con secciones (si es día) o con ejercicios (si es sección)
        $load = empty($data['parent_id']) ? 'sections' : 'exercises';

        return response()->json(new BlockResource($block->load($load)), 201);
    }

    /** PUT /routines/{routine}/blocks/{block} */
    public function update(Request $request, Routine $routine, Block $block): JsonResponse
    {
        $this->authorizeRoutineOwner($request, $routine);
        abort_if($block->routine_id !== $routine->id, 404);

        $data = $request->validate([
            'name'  => 'sometimes|string|max:100',
            'order' => 'sometimes|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $block->update($data);

        $load = $block->parent_id === null ? 'sections' : 'exercises';

        return response()->json(new BlockResource($block->load($load)));
    }

    /** DELETE /routines/{routine}/blocks/{block} */
    public function destroy(Request $request, Routine $routine, Block $block): JsonResponse
    {
        $this->authorizeRoutineOwner($request, $routine);
        abort_if($block->routine_id !== $routine->id, 404);

        $block->delete(); // Cascadea las secciones hijas por FK

        return response()->json(['message' => 'Bloque eliminado.']);
    }

    // ── Ejercicios dentro de una sección ─────────────────────────────────────

    /** POST /routines/{routine}/blocks/{block}/exercises */
    public function addExercise(Request $request, Routine $routine, Block $block): JsonResponse
    {
        $this->authorizeRoutineOwner($request, $routine);
        abort_if($block->routine_id !== $routine->id, 404);

        $data = $request->validate([
            'exercise_id'  => 'required|exists:exercises,id',
            'sets'         => 'integer|min:1',
            'reps'         => 'nullable|integer|min:1',
            'reps_max'     => 'nullable|integer|min:1|gte:reps',
            'duration_sec' => 'nullable|integer|min:1',
            'rest_sec'     => 'integer|min:0',
            'order'        => 'integer|min:0',
            'notes'        => 'nullable|string',
        ]);

        $exerciseId = $data['exercise_id'];
        $pivot      = array_filter(array_diff_key($data, ['exercise_id' => null]), fn($v) => $v !== null);

        $block->exercises()->attach($exerciseId, $pivot);

        return response()->json(new BlockResource($block->load('exercises')), 201);
    }

    /** PUT /routines/{routine}/blocks/{block}/exercises/{exerciseId} */
    public function updateExercise(Request $request, Routine $routine, Block $block, int $exerciseId): JsonResponse
    {
        $this->authorizeRoutineOwner($request, $routine);
        abort_if($block->routine_id !== $routine->id, 404);
        abort_unless($block->exercises()->where('exercise_id', $exerciseId)->exists(), 404);

        $data = $request->validate([
            'sets'         => 'sometimes|integer|min:1',
            'reps'         => 'nullable|integer|min:1',
            'reps_max'     => 'nullable|integer|min:1',
            'duration_sec' => 'nullable|integer|min:1',
            'rest_sec'     => 'sometimes|integer|min:0',
            'order'        => 'sometimes|integer|min:0',
            'notes'        => 'nullable|string',
        ]);

        $block->exercises()->updateExistingPivot($exerciseId, $data);

        return response()->json(new BlockResource($block->load('exercises')));
    }

    /** DELETE /routines/{routine}/blocks/{block}/exercises/{exerciseId} */
    public function removeExercise(Request $request, Routine $routine, Block $block, int $exerciseId): JsonResponse
    {
        $this->authorizeRoutineOwner($request, $routine);
        abort_if($block->routine_id !== $routine->id, 404);

        $block->exercises()->detach($exerciseId);

        return response()->json(['message' => 'Ejercicio removido del bloque.']);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function authorizeRoutineOwner(Request $request, Routine $routine): void
    {
        abort_if($request->user()->id !== $routine->owner_id, 403, 'Solo el dueño de la rutina puede modificarla.');
    }
}
