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
 * AzamPay mobile network operator (MNO) checkout driver. AzamPay issues an
 * OAuth2 access token from client credentials, then a checkout request
 * triggers the USSD push on the customer's MNO (Vodacom, Airtel, Tigo...).
 */
class AzampayGateway implements PaymentGatewayContract
{
    public function __construct(protected array $config)
    {
    }

    public function initiate(Payment $payment): array
    {
        $externalId = $payment->gateway_reference;

        $response = Http::withToken($this->token())
            ->baseUrl($this->config['checkout_url'])
            ->post('/azampay/mno/checkout', [
                'accountNumber' => $this->normalizePhone($payment->phone_number),
                'amount' => (string) $payment->amount,
                'currency' => $payment->currency,
                'externalId' => $externalId,
                'provider' => 'Airtel',
            ]);

        $body = $response->json() ?? [];

        if ($response->failed()) {
            Log::warning('AzamPay checkout failed', ['status' => $response->status(), 'body' => $body]);
        }

        return [
            'gateway_reference' => $externalId,
            'transaction_id' => data_get($body, 'transactionId') ?? data_get($body, 'data.transactionId'),
            'raw' => $body,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Azampay-Signature');

        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), (string) $this->config['webhook_secret']);

        return hash_equals($expected, $signature);
    }

    public function parseCallback(Request $request): array
    {
        $payload = $request->all();

        $transactionStatus = strtoupper((string) ($payload['transactionstatus'] ?? $payload['transactionStatus'] ?? ''));

        $status = match ($transactionStatus) {
            'SUCCESS', 'COMPLETED' => 'confirmed',
            'FAILED', 'CANCELLED' => 'failed',
            default => 'pending',
        };

        return [
            'reference' => (string) ($payload['externalid'] ?? $payload['externalId'] ?? ''),
            'transaction_id' => $payload['transactionid'] ?? $payload['transactionId'] ?? null,
            'status' => $status,
        ];
    }

    protected function token(): string
    {
        return Cache::remember('azampay:token', 3300, function () {
            $response = Http::asJson()
                ->baseUrl($this->config['base_url'])
                ->post('/AppRegistration/GenerateToken', [
                    'appName' => $this->config['app_name'],
                    'clientId' => $this->config['client_id'],
                    'clientSecret' => $this->config['client_secret'],
                ])
                ->throw();

            return data_get($response->json(), 'data.accessToken');
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
