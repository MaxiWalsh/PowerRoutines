<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ExerciseLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseLogController extends Controller
{
    public function __construct(private readonly ExerciseLogService $logService) {}

    /** Registrar una sesión de entrenamiento */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
            'routine_id'  => 'nullable|exists:routines,id',
            'block_id'    => 'nullable|exists:blocks,id',
            'session_id'  => 'nullable|string|max:36',
            'weight_kg'   => 'required|numeric|min:0',
            'reps'        => 'required|integer|min:1',
            'sets'        => 'required|integer|min:1',
            'notes'       => 'nullable|string|max:300',
            'logged_at'   => 'nullable|date',
        ]);

        $log = $this->logService->log($request->user(), $data);

        return response()->json($log->load('exercise'), 201);
    }

    /** Historial completo del usuario autenticado */
    public function myHistory(Request $request): JsonResponse
    {
        $logs = $request->user()
                        ->exerciseLogs()
                        ->with('exercise', 'routine')
                        ->orderByDesc('logged_at')
                        ->paginate(20);

        return response()->json($logs);
    }

    /** Historial de un ejercicio específico para el usuario autenticado */
    public function exerciseHistory(Request $request, int $exerciseId): JsonResponse
    {
        $logs = $this->logService->historyForExercise($request->user(), $exerciseId);

        return response()->json($logs);
    }

    /** Stats de un ejercicio: último peso y peso máximo */
    public function exerciseStats(Request $request, int $exerciseId): JsonResponse
    {
        $stats = $this->logService->statsForExercise($request->user(), $exerciseId);

        return response()->json($stats);
    }

    // ── Vista del trainer sobre sus students ─────────────────────────────────

    /** GET /students/{studentId} — datos básicos del alumno (solo el trainer del gym) */
    public function showStudent(Request $request, int $studentId): JsonResponse
    {
        $student = $this->resolveStudentForTrainer($request, $studentId);

        return response()->json([
            'id'         => $student->id,
            'name'       => $student->name,
            'email'      => $student->email,
            'avatar'     => $student->avatar,
            'weight_kg'  => $student->weight_kg,
            'height_cm'  => $student->height_cm,
            'gender'     => $student->gender,
        ]);
    }

    /** GET /students/{studentId}/logs — historial completo de un student (solo el trainer del gym) */
    public function studentHistory(Request $request, int $studentId): JsonResponse
    {
        $student = $this->resolveStudentForTrainer($request, $studentId);

        $logs = $student->exerciseLogs()
                        ->with('exercise', 'routine')
                        ->orderByDesc('logged_at')
                        ->paginate(20);

        return response()->json($logs);
    }

    /** GET /students/{studentId}/logs/exercise/{exerciseId}/stats */
    public function studentExerciseStats(Request $request, int $studentId, int $exerciseId): JsonResponse
    {
        $student = $this->resolveStudentForTrainer($request, $studentId);

        $stats = $this->logService->statsForExercise($student, $exerciseId);

        return response()->json($stats);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function resolveStudentForTrainer(Request $request, int $studentId): User
    {
        abort_unless($request->user()->isTrainer(), 403, 'Solo los trainers pueden ver el progreso de sus students.');

        $student = User::findOrFail($studentId);

        // Verificar que el student pertenece a un gym del trainer
        $trainerGymIds = \App\Models\Gym::where('trainer_id', $request->user()->id)->pluck('id');
        $studentGymIds = $student->gyms()->pluck('gyms.id');
        $shared = $trainerGymIds->intersect($studentGymIds);

        abort_if($shared->isEmpty(), 403, 'Este student no pertenece a ninguno de tus gimnasios.');

        return $student;
    }
}
