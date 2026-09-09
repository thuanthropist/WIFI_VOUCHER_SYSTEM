<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayContract;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Selcom Pay (apigw.selcommobile.com) USSD push driver.
 *
 * Selcom authenticates requests with a "SELCOM <base64 api key>" header plus
 * an HMAC-SHA256 "Digest" computed over the sorted request fields using the
 * api secret. The same scheme is reused to verify inbound webhooks.
 */
class SelcomGateway implements PaymentGatewayContract
{
    public function __construct(protected array $config)
    {
    }

    public function initiate(Payment $payment): array
    {
        $orderId = $payment->gateway_reference;

        $fields = [
            'vendor' => $this->config['vendor_id'],
            'order_id' => $orderId,
            'buyer_email' => 'guest@wifi-billing.local',
            'buyer_name' => 'WiFi Customer',
            'buyer_phone' => $this->normalizePhone($payment->phone_number),
            'amount' => (string) $payment->amount,
            'currency' => $payment->currency,
            'no_of_items' => 1,
            'webhook' => route('api.payments.callback', ['gateway' => 'selcom']),
        ];

        $response = Http::withHeaders($this->authHeaders($fields))
            ->baseUrl($this->config['base_url'])
            ->post('/v1/checkout/create-order-minimal', $fields);

        $body = $response->json() ?? [];

        if ($response->failed()) {
            Log::warning('Selcom order creation failed', ['status' => $response->status(), 'body' => $body]);
        }

        return [
            'gateway_reference' => $orderId,
            'transaction_id' => data_get($body, 'data.0.transid'),
            'raw' => $body,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('Digest');

        if (! $signature) {
            return false;
        }

        return hash_equals($this->sign($request->all()), $signature);
    }

    public function parseCallback(Request $request): array
    {
        $payload = $request->all();

        $resultCode = (string) ($payload['result_code'] ?? $payload['resultcode'] ?? '');
        $paymentStatus = $payload['payment_status'] ?? null;

        $status = match (true) {
            $resultCode === '000' || $paymentStatus === 'COMPLETED' => 'confirmed',
            $paymentStatus === 'FAILED' || $paymentStatus === 'CANCELLED' => 'failed',
            default => 'pending',
        };

        return [
            'reference' => (string) ($payload['order_id'] ?? $payload['reference'] ?? ''),
            'transaction_id' => $payload['transid'] ?? $payload['transaction_id'] ?? null,
            'status' => $status,
        ];
    }

    protected function authHeaders(array $fields): array
    {
        return [
            'Authorization' => 'SELCOM '.base64_encode((string) $this->config['api_key']),
            'Digest-Method' => 'HS256',
            'Digest' => $this->sign($fields),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    protected function sign(array $fields): string
    {
        ksort($fields);

        $data = collect($fields)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode('&');

        return base64_encode(hash_hmac('sha256', $data, (string) $this->config['api_secret'], true));
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (Str::startsWith($digits, '0')) {
            return '255'.substr($digits, 1);
        }

        return $digits;
    }
}
