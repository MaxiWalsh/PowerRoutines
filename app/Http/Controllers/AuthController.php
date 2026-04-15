<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ImageUploadService $imageService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()->load('profiles', 'gyms', 'ownedGym')));
    }

    public function updateMe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'sometimes|string|max:100',
            'avatar'     => 'nullable|url',
            'birth_date' => 'nullable|date',
            'gender'     => 'nullable|in:male,female,other',
            'weight_kg'  => 'nullable|numeric|min:0|max:500',
            'height_cm'  => 'nullable|numeric|min:0|max:300',
        ]);

        $request->user()->update($data);

        return response()->json(new UserResource($request->user()->fresh()->load('profiles', 'gyms')));
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();
        $previousPath = $user->avatar ? $this->imageService->pathFromUrl($user->avatar) : null;

        $url = $this->imageService->store($request->file('avatar'), 'avatars', $previousPath);

        $user->update(['avatar' => $url]);

        return response()->json(['avatar' => $url]);
    }

    /** POST /users/me/upgrade — activar plan premium (mock: sin pago real) */
    public function upgradePremium(Request $request): JsonResponse
    {
        $request->user()->update(['plan' => 'premium']);

        return response()->json(new UserResource(
            $request->user()->fresh()->load('profiles', 'gyms', 'ownedGym')
        ));
    }

    /** POST /users/me/downgrade — volver a plan free */
    public function downgradeFree(Request $request): JsonResponse
    {
        $request->user()->update(['plan' => 'free']);

        return response()->json(new UserResource(
            $request->user()->fresh()->load('profiles', 'gyms', 'ownedGym')
        ));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}
