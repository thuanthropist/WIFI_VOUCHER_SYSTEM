<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayContract;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ClickPesa USSD push driver. ClickPesa exchanges a client-id/api-key pair
 * for a short-lived bearer token, then pushes an STK/USSD prompt to the
 * payer's phone. Webhooks are authenticated with a shared secret header.
 */
class ClickPesaGateway implements PaymentGatewayContract
{
    public function __construct(protected array $config)
    {
    }

    public function initiate(Payment $payment): array
    {
        $orderReference = $payment->gateway_reference;

        $response = Http::withToken($this->token())
            ->baseUrl($this->config['base_url'])
            ->post('/third-parties/payments/initiate-ussd-push-request', [
                'amount' => (string) $payment->amount,
                'currency' => $payment->currency,
                'orderReference' => $orderReference,
                'phoneNumber' => $this->normalizePhone($payment->phone_number),
            ]);

        $body = $response->json() ?? [];

        if ($response->failed()) {
            Log::warning('ClickPesa push initiation failed', ['status' => $response->status(), 'body' => $body]);
        }

        return [
            'gateway_reference' => $orderReference,
            'transaction_id' => data_get($body, 'id') ?? data_get($body, 'transactionId'),
            'raw' => $body,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-ClickPesa-Signature') ?? $request->header('X-Webhook-Signature');

        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), (string) $this->config['webhook_secret']);

        return hash_equals($expected, $signature);
    }

    public function parseCallback(Request $request): array
    {
        $payload = $request->all();

        $eventStatus = strtoupper((string) ($payload['status'] ?? $payload['paymentStatus'] ?? ''));

        $status = match ($eventStatus) {
            'SUCCESS', 'SETTLED', 'COMPLETED' => 'confirmed',
            'FAILED', 'CANCELLED', 'DECLINED' => 'failed',
            default => 'pending',
        };

        return [
            'reference' => (string) ($payload['orderReference'] ?? $payload['reference'] ?? ''),
            'transaction_id' => $payload['id'] ?? $payload['transactionId'] ?? null,
            'status' => $status,
        ];
    }

    protected function token(): string
    {
        return Cache::remember('clickpesa:token', 3300, function () {
            $response = Http::asJson()
                ->baseUrl($this->config['base_url'])
                ->post('/third-parties/generate-token', [
                    'client_id' => $this->config['client_id'],
                    'api_key' => $this->config['api_key'],
                ])
                ->throw();

            return $response->json('token') ?? $response->json('accessToken');
        });
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
