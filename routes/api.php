<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\ExerciseLogController;
use App\Http\Controllers\GymController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoutineController;
use Illuminate\Support\Facades\Route;

// ── Health check ────────────────────────────────────────────────────────────
Route::get('/ping', fn() => ['pong' => true]);

// ── Webhooks (sin auth — los llama Mercado Pago) ─────────────────────────────
Route::post('/webhooks/mercadopago', [PaymentController::class, 'webhook']);

// ── Auth (públicas) ──────────────────────────────────────────────────────────
Route::prefix('users')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ── Rutas protegidas (requieren Sanctum token) ──────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('users/me',              [AuthController::class, 'me']);
    Route::put('users/me',              [AuthController::class, 'updateMe']);
    Route::post('users/me/avatar',      [AuthController::class, 'uploadAvatar']);
    Route::post('users/me/upgrade',     [AuthController::class, 'upgradePremium']);
    Route::post('users/me/downgrade',   [AuthController::class, 'downgradeFree']);
    Route::post('users/logout',         [AuthController::class, 'logout']);

    // Gimnasios
    Route::post('gyms',                              [GymController::class, 'store']);
    Route::get('gyms/{gym}',                         [GymController::class, 'show']);
    Route::put('gyms/{gym}',                         [GymController::class, 'update']);
    Route::post('gyms/{gym}/logo',                   [GymController::class, 'uploadLogo']);
    Route::post('gyms/join',                         [GymController::class, 'join']);
    Route::get('gyms/{gym}/students',                [GymController::class, 'students']);
    Route::delete('gyms/{gym}/leave',                [GymController::class, 'leave']);
    Route::delete('gyms/{gym}/students/{studentId}', [GymController::class, 'removeStudent']);

    // Rutinas (CRUD)
    Route::apiResource('routines', RoutineController::class);

    // Asignación de rutinas
    Route::post('routines/{routine}/assign/student/{studentId}', [RoutineController::class, 'assignToStudent']);
    Route::post('routines/{routine}/assign/gym/{gymId}',         [RoutineController::class, 'assignToGym']);
    Route::post('routines/{routine}/assign/profile/{profileId}', [RoutineController::class, 'assignToProfile']);
    Route::delete('routines/{routine}/assignments/{assignment}', [RoutineController::class, 'removeAssignment']);

    // Ejercicios (catálogo)
    Route::get('exercises',                [ExerciseController::class, 'index']);
    Route::post('exercises',               [ExerciseController::class, 'store']);
    Route::put('exercises/{exercise}',     [ExerciseController::class, 'update']);
    Route::delete('exercises/{exercise}',  [ExerciseController::class, 'destroy']);

    // Bloques dentro de una rutina
    Route::post('routines/{routine}/blocks',                                              [BlockController::class, 'store']);
    Route::put('routines/{routine}/blocks/{block}',                                       [BlockController::class, 'update']);
    Route::delete('routines/{routine}/blocks/{block}',                                    [BlockController::class, 'destroy']);
    Route::post('routines/{routine}/blocks/{block}/exercises',                            [BlockController::class, 'addExercise']);
    Route::put('routines/{routine}/blocks/{block}/exercises/{exerciseId}',                [BlockController::class, 'updateExercise']);
    Route::delete('routines/{routine}/blocks/{block}/exercises/{exerciseId}',             [BlockController::class, 'removeExercise']);

    // Perfiles de entrenamiento
    Route::get('profiles',                              [ProfileController::class, 'index']);
    Route::post('profiles',                             [ProfileController::class, 'store']);
    Route::put('profiles/{profile}',                    [ProfileController::class, 'update']);
    Route::delete('profiles/{profile}',                 [ProfileController::class, 'destroy']);
    Route::post('profiles/{profile}/users/{userId}',    [ProfileController::class, 'assignUser']);
    Route::delete('profiles/{profile}/users/{userId}',  [ProfileController::class, 'removeUser']);

    // Logs de entrenamiento (siempre sobre el usuario autenticado: /me/)
    Route::post('logs',                                           [ExerciseLogController::class, 'store']);
    Route::get('me/logs',                                         [ExerciseLogController::class, 'myHistory']);
    Route::get('me/logs/exercise/{exerciseId}',                   [ExerciseLogController::class, 'exerciseHistory']);
    Route::get('me/logs/exercise/{exerciseId}/stats',             [ExerciseLogController::class, 'exerciseStats']);

    // Marketplace
    Route::get('marketplace',                               [MarketplaceController::class, 'index']);
    Route::post('marketplace/{routine}/checkout',           [PaymentController::class, 'createPreference']);
    Route::get('marketplace/{routine}/purchase-status',     [PaymentController::class, 'purchaseStatus']);
    Route::post('routines/{routine}/publish',               [MarketplaceController::class, 'publish']);
    Route::delete('routines/{routine}/publish',             [MarketplaceController::class, 'unpublish']);
    Route::get('trainer/marketplace/stats',                 [MarketplaceController::class, 'trainerStats']);

    // Vista del trainer sobre sus students
    Route::get('students/{studentId}',                                   [ExerciseLogController::class, 'showStudent']);
    Route::get('students/{studentId}/logs',                              [ExerciseLogController::class, 'studentHistory']);
    Route::get('students/{studentId}/logs/exercise/{exerciseId}/stats',  [ExerciseLogController::class, 'studentExerciseStats']);
});
