<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\ProviderSyncService::class);

        \Laravel\Cashier\Cashier::ignoreMigrations();
    }

    public function boot(): void
    {
        // Only needed for MySQL with older innodb_large_prefix disabled
        if (config('database.default') === 'mysql') {
            Schema::defaultStringLength(191);
        }

        if ($this->app->environment('production')) {
            // Force HTTPS for all generated URLs
            URL::forceScheme('https');

            // Crash loudly in production if debug is enabled
            if (config('app.debug') === true) {
                abort(500, 'APP_DEBUG must be false in production. Check your .env file.');
            }
        }
    }
}