<?php

namespace App\Livewire\Portal;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Site;
use App\Models\Voucher;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CaptivePortal extends Component
{
    public ?int $siteId = null;

    /** buy | redeem */
    public string $activeTab = 'buy';

    public ?int $selectedPlanId = null;

    #[Validate(['required', 'regex:/^(0|255)[67]\d{8}$/'])]
    public string $phoneNumber = '';

    /** select-plan | phone | processing | connecting | connected | failed */
    public string $step = 'select-plan';

    public ?string $paymentReference = null;

    public ?string $voucherCode = null;

    public ?string $voucherExpiresAt = null;

    public ?string $voucherPlanName = null;

    public ?string $errorMessage = null;

    /** Fallback affordance on the "connected" screen if auto-login can't be confirmed. */
    public bool $showCodeFallback = false;

    // --- Omada's External Web Portal (RADIUS) redirect appends these to the
    // portal URL; they must be echoed back on the browserauth/radius/auth
    // POST alongside username/password. Exact casing is per TP-Link's own
    // demo package for the controller version in use — captured defensively
    // here across the casings seen in TP-Link's published documentation.
    public ?string $omadaClientMac = null;

    public ?string $omadaClientIp = null;

    public ?string $omadaApMac = null;

    public ?string $omadaGatewayMac = null;

    public ?string $omadaSsidName = null;

    public ?string $omadaVid = null;

    public ?string $omadaRadioId = null;

    public ?string $omadaOriginUrl = null;

    // --- "I have a code" tab (vouchers issued by staff / already purchased) ---
    #[Validate('required|string|min:4|max:20')]
    public string $redeemCode = '';

    public ?array $redeemResult = null;

    public function mount(?string $site = null): void
    {
        if ($site) {
            $this->siteId = Site::where('radius_nas_ip', $site)->value('id')
                ?? Site::find($site)?->id;
        }

        $this->omadaClientMac = $this->queryAny(['clientMac', 'clientmac']);
        $this->omadaClientIp = $this->queryAny(['clientIp', 'clientIP', 'clientip']) ?? request()->ip();
        $this->omadaApMac = $this->queryAny(['apMac', 'apmac']);
        $this->omadaGatewayMac = $this->queryAny(['gatewayMac', 'GatewayMac', 'gatewaymac']);
        $this->omadaSsidName = $this->queryAny(['ssidName', 'ssid']);
        $this->omadaVid = $this->queryAny(['vid']);
        $this->omadaRadioId = $this->queryAny(['radioId', 'radioid']);
        $this->omadaOriginUrl = $this->queryAny(['originUrl', 'originalUrl', 'origin_url']);
    }

    protected function queryAny(array $keys): ?string
    {
        foreach ($keys as $key) {
            if (request()->filled($key)) {
                return (string) request()->query($key);
            }
        }

        return null;
    }

    public function getPlansProperty(): Collection
    {
        return Plan::where('is_active', true)->orderBy('price')->get();
    }

    public function getSelectedPlanProperty(): ?Plan
    {
        return $this->selectedPlanId ? Plan::find($this->selectedPlanId) : null;
    }

    public function getSiteProperty(): ?Site
    {
        return $this->siteId ? Site::find($this->siteId) : null;
    }

    /**
     * The router's own captive-portal login form action, if the site has
     * one configured. When present, the "connecting" step auto-submits the
     * voucher as username/password here (in a hidden iframe) so the
     * customer never has to see or type a code.
     */
    public function getSiteLoginUrlProperty(): ?string
    {
        return $this->site?->login_url;
    }

    public function getIsOmadaProperty(): bool
    {
        return $this->site?->device_vendor === 'omada';
    }

    /** The plan with the lowest price-per-minute, highlighted as "Best Value" on the portal. */
    public function getBestValuePlanIdProperty(): ?int
    {
        return $this->plans
            ->sortBy(fn (Plan $plan) => $plan->duration_minutes > 0 ? $plan->price / $plan->duration_minutes : PHP_INT_MAX)
            ->first()
            ?->id;
    }

    public function switchTab(string $tab): void
    {
        if (! in_array($tab, ['buy', 'redeem'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->redeemResult = null;
        $this->resetErrorBag();
    }

    public function selectPlan(int $planId): void
    {
        $this->selectedPlanId = $planId;
        $this->step = 'phone';
    }

    public function backToPlans(): void
    {
        $this->step = 'select-plan';
        $this->selectedPlanId = null;
    }

    public function submitPhone(PaymentService $paymentService): void
    {
        $this->validate(['phoneNumber' => ['required', 'regex:/^(0|255)[67]\d{8}$/']]);

        $plan = $this->selectedPlan;

        if (! $plan) {
            $this->step = 'select-plan';

            return;
        }

        try {
            $payment = $paymentService->initiate(
                $this->phoneNumber,
                $plan,
                $this->siteId ? Site::find($this->siteId) : null
            );

            $this->paymentReference = $payment->gateway_reference;
            $this->step = 'processing';
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = 'We could not reach the payment network. Please try again.';
        }
    }

    /**
     * Polled every few seconds by wire:poll while step === 'processing'.
     * Also mirrors what a Laravel Echo listener bound to the
     * "payments.{reference}" channel would do on a "voucher.generated"
     * broadcast, without requiring a running Reverb server to demo.
     */
    public function pollStatus(): void
    {
        if ($this->step !== 'processing' || ! $this->paymentReference) {
            return;
        }

        $payment = Payment::with('voucher.plan')
            ->where('gateway_reference', $this->paymentReference)
            ->first();

        if (! $payment) {
            return;
        }

        if ($payment->status === 'confirmed' && $payment->voucher) {
            $this->voucherCode = $payment->voucher->code;
            $this->voucherExpiresAt = optional($payment->voucher->expires_at)
                ->diffForHumans(null, null, false, 1, Carbon::ROUND);
            $this->voucherPlanName = $payment->voucher->plan?->name;
            // "connecting" auto-submits the voucher to the router on the customer's
            // behalf; confirmConnected() below moves on to the thank-you screen.
            $this->step = 'connecting';
        } elseif ($payment->status === 'failed') {
            $this->step = 'failed';
        }
    }

    /**
     * Called from the client once the auto-login submission to the
     * router has had time to run, so we're not guessing a fixed delay.
     */
    public function confirmConnected(): void
    {
        if ($this->step === 'connecting') {
            $this->step = 'connected';
        }
    }

    public function toggleCodeFallback(): void
    {
        $this->showCodeFallback = ! $this->showCodeFallback;
    }

    public function startOver(): void
    {
        $this->reset([
            'selectedPlanId', 'phoneNumber', 'paymentReference',
            'voucherCode', 'voucherExpiresAt', 'voucherPlanName',
            'errorMessage', 'showCodeFallback',
        ]);
        $this->step = 'select-plan';
    }

    /**
     * "I have a code" tab: looks up a voucher a customer already holds
     * (bought online earlier, or handed to them by staff at the counter)
     * and shows its live status without needing another payment.
     */
    public function lookupVoucher(): void
    {
        $this->validate(['redeemCode' => 'required|string|min:4|max:20']);

        $voucher = Voucher::with('plan')
            ->whereRaw('UPPER(code) = ?', [strtoupper(trim($this->redeemCode))])
            ->first();

        if (! $voucher) {
            $this->redeemResult = [
                'found' => false,
                'message' => "We couldn't find that code. Double-check it or ask staff for a new one.",
            ];

            return;
        }

        $this->redeemResult = [
            'found' => true,
            'code' => $voucher->code,
            'status' => $voucher->status,
            'plan_name' => $voucher->plan?->name,
            'expires_at' => optional($voucher->expires_at)
                ->diffForHumans(null, null, false, 1, Carbon::ROUND),
            'is_expired' => $voucher->status === 'expired'
                || ($voucher->expires_at && $voucher->expires_at->isPast() && $voucher->status === 'unused'),
        ];
    }

    public function resetRedeem(): void
    {
        $this->reset(['redeemCode', 'redeemResult']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.portal.captive-portal', [
            'supportPhone' => Setting::get('support_phone', ''),
            'supportEmail' => Setting::get('support_email', ''),
        ]);
    }
}
