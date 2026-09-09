<?php

namespace App\Services;

use App\Jobs\GenerateVoucherJob;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public function __construct(protected PaymentGatewayManager $gateways)
    {
    }

    /**
     * Create a pending Payment record and push a USSD/STK charge to the
     * customer's phone via the configured gateway.
     */
    public function initiate(string $phoneNumber, Plan $plan, ?Site $site = null): Payment
    {
        $gatewayName = $this->gateways->getDefaultDriver();

        $payment = Payment::create([
            'phone_number' => $phoneNumber,
            'plan_id' => $plan->id,
            'site_id' => $site?->id,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'gateway' => $gatewayName,
            'gateway_reference' => (string) Str::uuid(),
            'status' => 'pending',
        ]);

        $result = $this->gateways->driver($gatewayName)->initiate($payment);

        $payment->update([
            'gateway_reference' => $result['gateway_reference'],
            'gateway_transaction_id' => $result['transaction_id'],
            'meta' => $result['raw'],
        ]);

        return $payment;
    }

    /**
     * Verify and process an inbound gateway webhook. Returns the updated
     * Payment. Throws if the signature does not check out.
     */
    public function handleCallback(Request $request, string $gatewayName): Payment
    {
        $gateway = $this->gateways->driver($gatewayName);

        if (! $gateway->verifyWebhookSignature($request)) {
            throw new RuntimeException('Invalid payment webhook signature.');
        }

        $data = $gateway->parseCallback($request);

        $payment = Payment::where('gateway_reference', $data['reference'])->firstOrFail();

        if ($data['status'] === 'confirmed' && ! $payment->isConfirmed()) {
            $payment->markConfirmed($data['transaction_id']);
            GenerateVoucherJob::dispatch($payment);
        } elseif ($data['status'] === 'failed' && $payment->status === 'pending') {
            $payment->markFailed();
        }

        return $payment;
    }
}
