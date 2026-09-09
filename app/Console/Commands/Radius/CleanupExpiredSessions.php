<?php

namespace App\Console\Commands\Radius;

use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\Voucher;
use Illuminate\Console\Command;

/**
 * Revokes RADIUS credentials for any voucher marked expired (whether it
 * timed out mid-session or was never redeemed in time), so the same
 * username/password can never authenticate again. Run this frequently —
 * it's what actually locks the door once radius:sync-voucher-sessions or
 * vouchers:cleanup-expired flips a voucher to "expired".
 *
 * Note: this does not forcibly disconnect an in-progress session — that
 * requires a RADIUS Disconnect-Request (CoA), which is vendor-specific and
 * not all routers support. In practice the NAS enforces the Session-Timeout
 * reply attribute set at provisioning time, which already caps how long an
 * active session can run.
 */
class CleanupExpiredSessions extends Command
{
    protected $signature = 'radius:cleanup-expired-sessions';

    protected $description = 'Delete radcheck/radreply entries for expired vouchers so they cannot re-authenticate';

    public function handle(): int
    {
        $usernames = Voucher::query()
            ->where('status', 'expired')
            ->pluck('radius_username');

        if ($usernames->isEmpty()) {
            $this->info('No expired vouchers with outstanding RADIUS credentials.');

            return self::SUCCESS;
        }

        $deletedCheck = RadCheck::whereIn('username', $usernames)->delete();
        $deletedReply = RadReply::whereIn('username', $usernames)->delete();

        $this->info("Revoked RADIUS access for {$usernames->count()} voucher(s) ({$deletedCheck} radcheck, {$deletedReply} radreply rows removed).");

        return self::SUCCESS;
    }
}
