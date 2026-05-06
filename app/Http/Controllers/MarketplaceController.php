<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoutineResource;
use App\Models\Routine;
use App\Models\RoutineAssignment;
use App\Models\RoutinePurchase;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    /** GET /marketplace — rutinas publicadas (paginado, con filtros) */
    public function index(Request $request): JsonResponse
    {
        $query = Routine::where('is_published', true)
            ->with(['owner', 'days'])
            ->withCount('purchases');

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('free')) {
            $query->where(fn($q) => $q->where('price', 0)->orWhereNull('price'));
        }

        if ($request->filled('discipline')) {
            $query->where('discipline', $request->discipline);
        }

        $routines = $query->orderByDesc('created_at')->paginate(20);

        $userId       = $request->user()->id;
        $purchasedIds = RoutinePurchase::where('user_id', $userId)->pluck('routine_id')->toArray();

        $routines->getCollection()->transform(function ($routine) use ($purchasedIds, $userId) {
            $routine->is_purchased   = in_array($routine->id, $purchasedIds);
            $routine->is_own_routine = $routine->owner_id === $userId;
            return $routine;
        });

        return response()->json(RoutineResource::collection($routines)->response()->getData(true));
    }

    /**
     * GET /marketplace/recommended
     * Rutinas que coinciden con el perfil del usuario autenticado:
     * misma disciplina, nivel compatible, sin contraindicaciones conflictivas.
     */
    public function recommended(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Routine::where('is_published', true)
            ->with(['owner', 'days'])
            ->withCount('purchases');

        // Filtrar por disciplinas del usuario (ahora es un array)
        if (!empty($user->disciplines)) {
            $query->whereIn('discipline', $user->disciplines);
        }

        // Filtrar por nivel compatible (principiante ve beginner, intermedio ve hasta intermediate, etc.)
        if ($user->fitness_level) {
            $levelOrder = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3];
            $userLevel  = $levelOrder[$user->fitness_level] ?? 1;

            $compatibleLevels = array_keys(array_filter(
                $levelOrder,
                fn($v) => $v <= $userLevel
            ));

            $query->where(function ($q) use ($compatibleLevels) {
                $q->whereIn('target_level', $compatibleLevels)
                  ->orWhereNull('target_level');
            });
        }

        $routines = $query->orderByDesc('purchases_count')->limit(10)->get();

        $purchasedIds = RoutinePurchase::where('user_id', $user->id)->pluck('routine_id')->toArray();

        // Filtrar contraindicaciones en PHP (json array matching)
        $userConditions = $user->conditions ?? [];

        $routines = $routines->filter(function ($routine) use ($userConditions) {
            if (empty($userConditions) || empty($routine->contraindications)) {
                return true;
            }
            // Excluir si hay intersección entre condiciones del user y contraindicaciones de la rutina
            return empty(array_intersect($userConditions, $routine->contraindications));
        })->values();

        $routines->transform(function ($routine) use ($purchasedIds, $user) {
            $routine->is_purchased   = in_array($routine->id, $purchasedIds);
            $routine->is_own_routine = $routine->owner_id === $user->id;
            return $routine;
        });

        return response()->json(RoutineResource::collection($routines));
    }

    /** POST /marketplace/{routine}/purchase — comprar una rutina */
    public function purchase(Request $request, Routine $routine): JsonResponse
    {
        $user = $request->user();

        abort_unless($routine->is_published, 404, 'Esta rutina no está disponible en el marketplace.');
        abort_if($routine->owner_id === $user->id, 422, 'No podés comprar tu propia rutina.');

        $alreadyPurchased = RoutinePurchase::where('user_id', $user->id)
            ->where('routine_id', $routine->id)
            ->exists();

        abort_if($alreadyPurchased, 422, 'Ya compraste esta rutina.');

        RoutinePurchase::create([
            'user_id'    => $user->id,
            'routine_id' => $routine->id,
            'price_paid' => $routine->price ?? 0,
        ]);

        RoutineAssignment::create([
            'routine_id'      => $routine->id,
            'assigned_by'     => $routine->owner_id,
            'assignable_type' => User::class,
            'assignable_id'   => $user->id,
            'notes'           => 'Adquirida en el marketplace.',
        ]);

        return response()->json(['message' => '¡Rutina adquirida! Ya aparece en tu lista de rutinas.']);
    }

    /** POST /routines/{routine}/publish — trainer publica su rutina */
    public function publish(Request $request, Routine $routine, ImageUploadService $imageService): JsonResponse
    {
        abort_if($routine->owner_id !== $request->user()->id, 403, 'Solo el dueño puede publicar esta rutina.');

        $data = $request->validate([
            'price'                   => 'required|numeric|min:0',
            'marketplace_description' => 'required|string|max:600',
            'difficulty'              => 'required|in:beginner,intermediate,advanced',
            'duration_weeks'          => 'nullable|integer|min:1|max:52',
            'days_per_week'           => 'nullable|integer|min:1|max:7',
            'cover_image'             => 'nullable|url',
            'cover_image_file'        => 'nullable|file|image|max:4096',
            'discipline'              => 'nullable|string|max:50',
            'target_goals'            => 'nullable|array',
            'target_goals.*'          => 'string|max:50',
            'target_level'            => 'nullable|in:beginner,intermediate,advanced',
            'contraindications'       => 'nullable|array',
            'contraindications.*'     => 'string|max:50',
        ]);

        if ($request->hasFile('cover_image_file')) {
            try {
                $data['cover_image'] = $imageService->store($request->file('cover_image_file'), 'marketplace-covers');
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'No se pudo subir la imagen de portada. Verificá tu conexión e intentá de nuevo.',
                ], 422);
            }
        } elseif (empty($data['cover_image'])) {
            $data['cover_image'] = null;
        }

        unset($data['cover_image_file']);

        $routine->update(array_merge($data, ['is_published' => true]));

        return response()->json([
            'message' => '¡Rutina publicada en el marketplace!',
            'routine' => new RoutineResource($routine->fresh()),
        ]);
    }

    /** DELETE /routines/{routine}/publish — trainer retira su rutina del marketplace */
    public function unpublish(Request $request, Routine $routine): JsonResponse
    {
        abort_if($routine->owner_id !== $request->user()->id, 403);

        $routine->update(['is_published' => false]);

        return response()->json(['message' => 'Rutina retirada del marketplace.']);
    }

    /** GET /trainer/marketplace/stats — estadísticas de ventas del trainer */
    public function trainerStats(Request $request): JsonResponse
    {
        $user = $request->user();

        $routineIds = Routine::where('owner_id', $user->id)->pluck('id');

        $purchases = RoutinePurchase::whereIn('routine_id', $routineIds)
            ->with('routine:id,name,price')
            ->get();

        $totalRevenue    = $purchases->sum('price_paid');
        $platformFee     = $totalRevenue * 0.30;
        $trainerEarnings = $totalRevenue * 0.70;

        $byRoutine = $purchases->groupBy('routine_id')->map(fn($group) => [
            'routine_name' => $group->first()->routine->name,
            'sales'        => $group->count(),
            'revenue'      => $group->sum('price_paid'),
        ])->values();

        return response()->json([
            'total_sales'      => $purchases->count(),
            'total_revenue'    => round($totalRevenue, 2),
            'platform_fee'     => round($platformFee, 2),
            'trainer_earnings' => round($trainerEarnings, 2),
            'by_routine'       => $byRoutine,
        ]);
    }
}
