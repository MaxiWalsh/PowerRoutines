<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoutineResource;
use App\Models\Routine;
use App\Models\RoutineAssignment;
use App\Models\RoutinePurchase;
use App\Services\RoutineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoutineController extends Controller
{
    public function __construct(private readonly RoutineService $routineService) {}

    /** Rutinas visibles para el usuario autenticado (propias + asignadas) */
    public function index(Request $request): JsonResponse
    {
        $routines = $this->routineService->getForUser($request->user());

        // Taggear cada rutina con su origen para que el frontend pueda agruparlas
        $userId       = $request->user()->id;
        $purchasedIds = RoutinePurchase::where('user_id', $userId)->pluck('routine_id')->toArray();

        $routines->getCollection()->transform(function ($routine) use ($userId, $purchasedIds) {
            if ($routine->owner_id === $userId) {
                $routine->source = 'own';
            } elseif (in_array($routine->id, $purchasedIds)) {
                $routine->source = 'marketplace';
            } else {
                $routine->source = 'trainer';
            }
            return $routine;
        });

        return response()->json(RoutineResource::collection($routines)->response()->getData(true));
    }

    public function show(Routine $routine): JsonResponse
    {
        $this->authorize('view', $routine);

        return response()->json(new RoutineResource($routine->load('days.sections.exercises', 'assignments')));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'scope'       => 'in:personal,gym,profile,student',
            'is_template' => 'boolean',
        ]);

        $routine = $this->routineService->create($request->user(), $data);

        return response()->json(new RoutineResource($routine), 201);
    }

    public function update(Request $request, Routine $routine): JsonResponse
    {
        $this->authorize('update', $routine);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'scope'       => 'in:personal,gym,profile,student',
            'is_template' => 'boolean',
            'is_active'   => 'boolean',
        ]);

        $routine = $this->routineService->update($routine, $data);

        return response()->json(new RoutineResource($routine));
    }

    public function destroy(Routine $routine): JsonResponse
    {
        $this->authorize('delete', $routine);

        $this->routineService->delete($routine);

        return response()->json(['message' => 'Rutina eliminada.']);
    }

    // ── Asignaciones ─────────────────────────────────────────────────────────

    /** Asignar a un alumno específico */
    public function assignToStudent(Request $request, Routine $routine, int $studentId): JsonResponse
    {
        $this->authorize('assign', $routine);

        $extra = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after:start_date',
            'notes'      => 'nullable|string',
        ]);

        $assignment = $this->routineService->assignToStudent($routine, $request->user(), $studentId, $extra);

        return response()->json($assignment, 201);
    }

    /** Asignar a todo el gimnasio */
    public function assignToGym(Request $request, Routine $routine, int $gymId): JsonResponse
    {
        $this->authorize('assign', $routine);

        $extra = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after:start_date',
            'notes'      => 'nullable|string',
        ]);

        $assignment = $this->routineService->assignToGym($routine, $request->user(), $gymId, $extra);

        return response()->json($assignment, 201);
    }

    /** Asignar a un perfil de entrenamiento */
    public function assignToProfile(Request $request, Routine $routine, int $profileId): JsonResponse
    {
        $this->authorize('assign', $routine);

        $extra = $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after:start_date',
            'notes'      => 'nullable|string',
        ]);

        $assignment = $this->routineService->assignToProfile($routine, $request->user(), $profileId, $extra);

        return response()->json($assignment, 201);
    }

    /** Eliminar una asignación */
    public function removeAssignment(Routine $routine, RoutineAssignment $assignment): JsonResponse
    {
        $this->authorize('assign', $routine);

        $this->routineService->removeAssignment($assignment);

        return response()->json(['message' => 'Asignación eliminada.']);
    }
}
