<?php

namespace App\Events;

use App\Models\Voucher;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the captive portal the instant a voucher is ready, so the
 * "Payment confirmed!" screen updates without polling.
 */
class VoucherGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Voucher $voucher)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('payments.'.$this->voucher->payment->gateway_reference),
        ];
    }

    public function broadcastAs(): string
    {
        return 'voucher.generated';
    }

    public function broadcastWith(): array
    {
        return [
            'voucher_code' => $this->voucher->code,
            'plan_name' => $this->voucher->plan->name,
            'expires_at' => optional($this->voucher->expires_at)->toIso8601String(),
        ];
    }
}
