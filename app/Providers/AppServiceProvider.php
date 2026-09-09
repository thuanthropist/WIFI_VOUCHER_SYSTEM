<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayContract;
use App\Models\Setting;
use App\Services\PaymentGatewayManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class);

        $this->app->bind(
            PaymentGatewayContract::class,
            fn ($app) => $app->make(PaymentGatewayManager::class)->driver()
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole() && ! Schema::hasTable('settings')) {
            return;
        }

        try {
            if ($appName = Setting::get('app_name')) {
                config(['app.name' => $appName]);
            }
        } catch (\Throwable) {
            // settings table not migrated yet (fresh install) — fall back to config/app.php.
        }
    }
}
