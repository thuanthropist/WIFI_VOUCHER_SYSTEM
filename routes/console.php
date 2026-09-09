<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep voucher status in sync with what actually happened on the RADIUS
// server, then immediately revoke credentials for anything that just
// expired, so a redeemed-but-timed-out or never-redeemed voucher can't be
// used to authenticate again.
Schedule::command('radius:sync-voucher-sessions')->everyFiveMinutes();
Schedule::command('radius:cleanup-expired-sessions')->everyFiveMinutes();

// Daily housekeeping: sweep vouchers that were never redeemed before their
// deadline, and email the admin a revenue summary for the day just closed.
Schedule::command('vouchers:cleanup-expired')->dailyAt('00:10');
Schedule::command('reports:daily-revenue')->dailyAt('06:00');
