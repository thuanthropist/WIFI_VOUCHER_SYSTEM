<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Plans\Index as AdminPlans;
use App\Livewire\Admin\Settings\Index as AdminSettings;
use App\Livewire\Admin\Sites\Index as AdminSites;
use App\Livewire\Admin\Users\Index as AdminUsers;
use App\Livewire\Admin\Vouchers\Index as AdminVouchers;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('portal.home'));

// Captive portal: the page a site's router redirects unauthenticated
// clients to. The router is expected to append ?site=<radius_nas_ip> (or
// the numeric Site id) so the correct plan list / branding can be shown.
Route::get('/portal', function () {
    return view('portal.index');
})->name('portal.home');

// Breeze's auth controllers all fall back to route('dashboard') when there is
// no "intended" URL to return to — redirecting it straight into the admin
// panel means every post-login/register/verify path lands on /admin instead
// of Laravel's default scaffold page.
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboard::class)->name('dashboard');
    Route::get('/plans', AdminPlans::class)->name('plans.index');
    Route::get('/sites', AdminSites::class)->name('sites.index');
    Route::get('/vouchers', AdminVouchers::class)->name('vouchers.index');

    Route::middleware('admin')->group(function () {
        Route::get('/users', AdminUsers::class)->name('users.index');
        Route::get('/settings', AdminSettings::class)->name('settings.index');
    });
});

require __DIR__.'/auth.php';
