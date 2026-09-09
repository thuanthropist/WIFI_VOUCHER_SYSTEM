<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiatePaymentRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Site;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    /**
     * POST /api/payments/initiate
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $plan = Plan::findOrFail($request->integer('plan_id'));

        if (! $plan->is_active) {
            throw ValidationException::withMessages([
                'plan_id' => 'This plan is no longer available.',
            ]);
        }

        $site = $request->filled('site_id') ? Site::find($request->integer('site_id')) : null;

        $payment = $this->paymentService->initiate($request->string('phone_number'), $plan, $site);

        return response()->json([
            'reference' => $payment->gateway_reference,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * GET /api/payments/{reference}/status — fallback for clients that
     * can't hold a websocket connection open.
     */
    public function status(string $reference): JsonResponse
    {
        $payment = Payment::with('voucher')->where('gateway_reference', $reference)->firstOrFail();

        return response()->json([
            'status' => $payment->status,
            'voucher' => $payment->voucher ? [
                'code' => $payment->voucher->code,
                'expires_at' => optional($payment->voucher->expires_at)->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * POST /api/payments/callback/{gateway} — webhook, signature-verified
     * inside PaymentService rather than relying on Laravel's CSRF token.
     */
    public function callback(Request $request, string $gateway): JsonResponse
    {
        try {
            $this->paymentService->handleCallback($request, $gateway);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }

        return response()->json(['message' => 'ok']);
    }
}
