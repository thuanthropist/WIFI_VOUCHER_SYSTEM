<?php

namespace App\Jobs;

use App\Events\VoucherGenerated;
use App\Models\Payment;
use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\Setting;
use App\Models\Site;
use App\Models\Voucher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a confirmed Payment into working network access: a RADIUS user
 * (radcheck/radreply) any FreeRADIUS-fronted router can authenticate
 * against, plus the Voucher record the captive portal shows the customer.
 */
class GenerateVoucherJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Payment $payment)
    {
    }

    public function handle(): void
    {
        if ($this->payment->voucher()->exists()) {
            return;
        }

        $plan = $this->payment->plan;
        $site = $this->payment->site;
        $code = $this->generateUniqueCode();

        $voucher = DB::transaction(function () use ($plan, $site, $code) {
            return Voucher::create([
                'code' => $code,
                'radius_username' => $code,
                'radius_password' => $code,
                'plan_id' => $plan->id,
                'payment_id' => $this->payment->id,
                'site_id' => $site?->id,
                'status' => 'unused',
                // Redemption deadline: how long the customer has to actually
                // connect before the voucher is swept up as unused. The
                // RADIUS Session-Timeout reply attribute (below) governs the
                // connected-time budget once it *is* redeemed.
                'expires_at' => now()->addMinutes(max(
                    $plan->duration_minutes * (int) Setting::get('voucher_redeem_multiplier', 3),
                    60
                )),
            ]);
        });

        $this->provisionRadiusUser($voucher, $plan, $site);

        event(new VoucherGenerated($voucher->fresh(['plan', 'payment'])));
    }

    protected function provisionRadiusUser(Voucher $voucher, $plan, $site): void
    {
        RadCheck::create([
            'username' => $voucher->radius_username,
            'attribute' => 'Cleartext-Password',
            'op' => ':=',
            'value' => $voucher->radius_password,
        ]);

        RadReply::create([
            'username' => $voucher->radius_username,
            'attribute' => 'Session-Timeout',
            'op' => ':=',
            'value' => (string) ($plan->duration_minutes * 60),
        ]);

        foreach ($this->bandwidthAttributes($site, $plan->speed_limit_mbps) as $attribute) {
            RadReply::create(array_merge(['username' => $voucher->radius_username], $attribute));
        }
    }

    /**
     * Vendor-agnostic bandwidth shaping: pick the RADIUS reply attribute(s)
     * the site's router brand actually understands. This is the crux of
     * staying router-agnostic — new hardware just needs a `match` arm here,
     * never a vendor SDK integration.
     */
    protected function bandwidthAttributes(?Site $site, ?int $mbps): array
    {
        if (! $mbps) {
            return [];
        }

        $vendor = $site->device_vendor ?? 'mikrotik';
        $bitsPerSecond = (string) ($mbps * 1_000_000);

        return match ($vendor) {
            'mikrotik' => [
                ['attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => "{$mbps}M/{$mbps}M"],
            ],
            default => [
                ['attribute' => 'WISPr-Bandwidth-Max-Up', 'op' => ':=', 'value' => $bitsPerSecond],
                ['attribute' => 'WISPr-Bandwidth-Max-Down', 'op' => ':=', 'value' => $bitsPerSecond],
            ],
        };
    }

    protected function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Voucher::where('code', $code)->exists());

        return $code;
    }
}
