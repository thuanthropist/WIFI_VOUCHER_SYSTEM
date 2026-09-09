<?php

namespace App\Console\Commands\Vouchers;

use App\Models\Voucher;
use Illuminate\Console\Command;

/**
 * Daily housekeeping on the vouchers table: anything past its redemption
 * deadline that was never used gets marked expired. Actually revoking the
 * matching RADIUS credentials is handled separately by
 * radius:cleanup-expired-sessions, which also runs against already-expired
 * vouchers, so nothing here needs to touch radcheck/radreply.
 */
class CleanupExpiredVouchers extends Command
{
    protected $signature = 'vouchers:cleanup-expired';

    protected $description = 'Mark unused vouchers past their redemption deadline as expired';

    public function handle(): int
    {
        $count = Voucher::query()
            ->where('status', 'unused')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Marked {$count} unused voucher(s) as expired.");

        return self::SUCCESS;
    }
}
