<?php

namespace App\Http\Controllers;

use App\Http\Resources\GymResource;
use App\Http\Resources\UserResource;
use App\Models\Gym;
use App\Services\GymService;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GymController extends Controller
{
    public function __construct(
        private readonly GymService $gymService,
        private readonly ImageUploadService $imageService,
    ) {}

    /** Trainer crea su gimnasio */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Gym::class);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $gym = $this->gymService->create($request->user(), $data);

        return response()->json(new GymResource($gym), 201);
    }

    /** Ver detalle de un gym */
    public function show(Gym $gym): JsonResponse
    {
        return response()->json(new GymResource($gym->load('trainer', 'profiles')));
    }

    /** Trainer actualiza su gym */
    public function update(Request $request, Gym $gym): JsonResponse
    {
        $this->authorize('update', $gym);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $gym = $this->gymService->update($gym, $data);

        return response()->json(new GymResource($gym));
    }

    /** Student se une al gym con un código de invitación */
    public function join(Request $request): JsonResponse
    {
        $request->validate(['invite_code' => 'required|string']);

        $gym = $this->gymService->joinByCode($request->user(), $request->invite_code);

        return response()->json([
            'message' => "Te uniste a {$gym->name} correctamente.",
            'gym'     => new GymResource($gym),
        ]);
    }

    /** Listar students de un gym (solo el trainer) */
    public function students(Request $request, Gym $gym): JsonResponse
    {
        $this->authorize('viewStudents', $gym);

        $students = $this->gymService->getStudents($gym);

        return response()->json(UserResource::collection($students)->response()->getData(true));
    }

    /** Trainer sube/actualiza el logo del gym */
    public function uploadLogo(Request $request, Gym $gym): JsonResponse
    {
        $this->authorize('update', $gym);

        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $previousPath = $gym->logo ? $this->imageService->pathFromUrl($gym->logo) : null;
        $url = $this->imageService->store($request->file('logo'), 'gyms', $previousPath);

        $gym->update(['logo' => $url]);

        return response()->json(['logo' => $url]);
    }

    /** Student abandona el gym por su cuenta */
    public function leave(Request $request, Gym $gym): JsonResponse
    {
        $this->gymService->leaveGym($gym, $request->user());

        return response()->json(['message' => "Saliste de {$gym->name} correctamente."]);
    }

    /** Trainer remueve un student */
    public function removeStudent(Gym $gym, int $studentId): JsonResponse
    {
        $this->authorize('update', $gym);

        $this->gymService->removeStudent($gym, $studentId);

        return response()->json(['message' => 'Alumno removido del gimnasio.']);
    }
}
