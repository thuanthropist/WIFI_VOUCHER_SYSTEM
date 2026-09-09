<?php

namespace App\Console\Commands\Reports;

use App\Mail\DailyRevenueReport;
use App\Models\Payment;
use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyRevenueReport extends Command
{
    protected $signature = 'reports:daily-revenue';

    protected $description = 'Email the previous day\'s revenue and voucher summary to the admin';

    public function handle(): int
    {
        $recipient = config('mail.admin_report_address');

        if (! $recipient) {
            $this->warn('ADMIN_REPORT_EMAIL is not set — skipping daily revenue report.');

            return self::SUCCESS;
        }

        $day = now()->subDay()->toDateString();

        $revenue = Payment::query()
            ->where('status', 'confirmed')
            ->whereDate('paid_at', $day)
            ->sum('amount');

        $paymentsCount = Payment::query()
            ->where('status', 'confirmed')
            ->whereDate('paid_at', $day)
            ->count();

        $vouchersIssued = Voucher::whereDate('created_at', $day)->count();

        Mail::to($recipient)->send(new DailyRevenueReport($day, $revenue, $paymentsCount, $vouchersIssued));

        $this->info("Daily revenue report for {$day} sent to {$recipient}.");

        return self::SUCCESS;
    }
}
