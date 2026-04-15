<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use App\Models\RoutineAssignment;
use App\Models\RoutinePurchase;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;

class PaymentController extends Controller
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL); // sandbox
    }

    /**
     * POST /marketplace/{routine}/checkout
     * Crea una preferencia de pago en Mercado Pago y devuelve la URL de checkout.
     */
    public function createPreference(Request $request, Routine $routine): JsonResponse
    {
        $user = $request->user();

        abort_unless($routine->is_published, 404, 'Esta rutina no está disponible en el marketplace.');
        abort_if($routine->owner_id === $user->id, 422, 'No podés comprar tu propia rutina.');

        $alreadyPurchased = RoutinePurchase::where('user_id', $user->id)
            ->where('routine_id', $routine->id)
            ->exists();

        abort_if($alreadyPurchased, 422, 'Ya compraste esta rutina.');

        // Rutinas gratuitas: compra directa sin pago
        if (empty($routine->price) || $routine->price <= 0) {
            return $this->completeFreeRoutine($user, $routine);
        }

        $frontendUrl = config('services.mercadopago.frontend_url', 'http://localhost:5173');

        $client = new PreferenceClient();

        $preferenceData = [
            'items' => [[
                'id'          => (string) $routine->id,
                'title'       => $routine->name,
                'description' => $routine->marketplace_description ?? "Rutina de entrenamiento: {$routine->name}",
                'quantity'    => 1,
                'currency_id' => 'ARS',
                'unit_price'  => (float) $routine->price,
            ]],
            'payer' => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'back_urls' => [
                'success' => "{$frontendUrl}/student/marketplace?payment=success&routine={$routine->id}",
                'failure' => "{$frontendUrl}/student/marketplace?payment=failure&routine={$routine->id}",
                'pending' => "{$frontendUrl}/student/marketplace?payment=pending&routine={$routine->id}",
            ],
            'auto_return'        => 'approved',
            'external_reference' => "routine_{$routine->id}_user_{$user->id}",
            'notification_url'   => url('/api/webhooks/mercadopago'),
            'metadata' => [
                'routine_id' => $routine->id,
                'user_id'    => $user->id,
            ],
        ];

        try {
            $preference = $client->create($preferenceData);

            return response()->json([
                'preference_id'      => $preference->id,
                'checkout_url'       => $preference->init_point,       // producción
                'sandbox_url'        => $preference->sandbox_init_point, // sandbox
                'public_key'         => config('services.mercadopago.public_key'),
                'is_sandbox'         => app()->environment('local', 'staging'),
            ]);

        } catch (MPApiException $e) {
            Log::error('MercadoPago preference error', [
                'message'    => $e->getMessage(),
                'statusCode' => $e->getApiResponse()?->getStatusCode(),
            ]);
            return response()->json(['message' => 'Error al iniciar el pago. Intentá de nuevo.'], 500);
        }
    }

    /**
     * POST /webhooks/mercadopago
     * Webhook de Mercado Pago — confirma el pago y otorga la rutina.
     */
    public function webhook(Request $request): JsonResponse
    {
        $type = $request->input('type') ?? $request->input('topic');

        if ($type !== 'payment') {
            return response()->json(['status' => 'ignored']);
        }

        $paymentId = $request->input('data.id') ?? $request->input('id');

        if (!$paymentId) {
            return response()->json(['status' => 'no_payment_id']);
        }

        try {
            $client  = new PaymentClient();
            $payment = $client->get((int) $paymentId);

            if ($payment->status !== 'approved') {
                return response()->json(['status' => 'not_approved', 'payment_status' => $payment->status]);
            }

            $metadata  = $payment->metadata ?? null;
            $routineId = $metadata?->routine_id ?? null;
            $userId    = $metadata?->user_id    ?? null;

            // Fallback: parsear external_reference "routine_X_user_Y"
            if (!$routineId || !$userId) {
                $ref = $payment->external_reference ?? '';
                if (preg_match('/routine_(\d+)_user_(\d+)/', $ref, $m)) {
                    $routineId = (int) $m[1];
                    $userId    = (int) $m[2];
                }
            }

            if (!$routineId || !$userId) {
                Log::warning('MP webhook: no se pudo identificar routine/user', ['payment_id' => $paymentId]);
                return response()->json(['status' => 'missing_reference']);
            }

            $this->grantRoutineAccess((int) $userId, (int) $routineId, (float) ($payment->transaction_amount ?? 0));

            return response()->json(['status' => 'ok']);

        } catch (\Throwable $e) {
            Log::error('MercadoPago webhook error', ['error' => $e->getMessage(), 'payment_id' => $paymentId]);
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * GET /marketplace/{routine}/purchase-status
     * El frontend puede consultar si una compra fue procesada (luego del redirect de MP).
     */
    public function purchaseStatus(Request $request, Routine $routine): JsonResponse
    {
        $purchased = RoutinePurchase::where('user_id', $request->user()->id)
            ->where('routine_id', $routine->id)
            ->exists();

        return response()->json(['purchased' => $purchased]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function completeFreeRoutine(User $user, Routine $routine): JsonResponse
    {
        $this->grantRoutineAccess($user->id, $routine->id, 0);

        return response()->json([
            'purchased' => true,
            'message'   => '¡Rutina gratuita adquirida! Ya aparece en tu lista.',
        ]);
    }

    private function grantRoutineAccess(int $userId, int $routineId, float $pricePaid): void
    {
        // Evitar duplicados
        $exists = RoutinePurchase::where('user_id', $userId)
            ->where('routine_id', $routineId)
            ->exists();

        if ($exists) return;

        RoutinePurchase::create([
            'user_id'    => $userId,
            'routine_id' => $routineId,
            'price_paid' => $pricePaid,
        ]);

        RoutineAssignment::firstOrCreate(
            [
                'routine_id'      => $routineId,
                'assignable_type' => User::class,
                'assignable_id'   => $userId,
            ],
            [
                'assigned_by' => Routine::find($routineId)?->owner_id,
                'notes'       => 'Adquirida en el marketplace.',
            ]
        );
    }
}
