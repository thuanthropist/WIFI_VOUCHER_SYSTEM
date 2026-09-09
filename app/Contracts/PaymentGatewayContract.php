<?php

namespace App\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Vendor-agnostic contract every mobile money gateway driver must satisfy
 * (Selcom, ClickPesa, Azampay, ...). PaymentService and the payment
 * controller only ever talk to this interface, never a concrete driver.
 */
interface PaymentGatewayContract
{
    /**
     * Kick off a USSD/STK push charge for the given pending payment.
     *
     * @return array{gateway_reference: string, transaction_id: ?string, raw: array}
     */
    public function initiate(Payment $payment): array;

    /**
     * Verify that an inbound webhook request genuinely originated from
     * this gateway (HMAC signature, shared secret header, etc.) before
     * any of its contents are trusted.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Normalise a verified webhook payload into a status update.
     *
     * @return array{reference: string, transaction_id: ?string, status: 'confirmed'|'failed'|'pending'}
     */
    public function parseCallback(Request $request): array;
}
