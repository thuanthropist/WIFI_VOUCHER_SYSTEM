<?php

namespace App\Console\Commands\Radius;

use App\Models\RadAcct;
use App\Models\Voucher;
use Illuminate\Console\Command;

/**
 * FreeRADIUS authenticates directly against the shared MySQL tables, so it
 * never calls back into Laravel when a voucher is actually redeemed. This
 * command polls radacct — the source of truth for "did this username ever
 * start a session" — to keep the vouchers table's status in sync:
 *
 *   unused -> active   (a radacct row appeared: the code was redeemed)
 *   active -> expired  (the connected-time budget has now elapsed)
 */
class SyncVoucherSessions extends Command
{
    protected $signature = 'radius:sync-voucher-sessions';

    protected $description = 'Sync voucher status (unused/active/expired) against RADIUS accounting data';

    public function handle(): int
    {
        // radcheck/radacct live on a separate DB connection from vouchers,
        // so a query-builder whereIn subquery can't join across them —
        // pull the usernames that have started a session explicitly.
        $startedUsernames = RadAcct::query()->distinct()->pluck('username');

        $activatedCount = Voucher::query()
            ->where('status', 'unused')
            ->whereIn('radius_username', $startedUsernames)
            ->get()
            ->each(function (Voucher $voucher) {
                $session = RadAcct::forUsername($voucher->radius_username)->oldest('acctstarttime')->first();

                $activatedAt = $session?->acctstarttime ?? now();

                $voucher->update([
                    'status' => 'active',
                    'activated_at' => $activatedAt,
                    'expires_at' => $activatedAt->clone()->addMinutes($voucher->plan->duration_minutes),
                ]);
            })
            ->count();

        $expiredCount = Voucher::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Activated {$activatedCount} voucher(s), expired {$expiredCount} voucher(s).");

        return self::SUCCESS;
    }
}
