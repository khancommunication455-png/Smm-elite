<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateEnvironment extends Command
{
    protected $signature = 'env:validate';
    protected $description = 'Validate required production environment variables and insecure toggles';

    public function handle(): int
    {
        $required = [
            'APP_NAME',
            'APP_ENV',
            'APP_KEY',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'QUEUE_CONNECTION',
            'CACHE_DRIVER',
            'SESSION_DRIVER',
            'REDIS_HOST',
            'MAIL_MAILER',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS',
            'STRIPE_KEY',
            'STRIPE_SECRET',
            'STRIPE_WEBHOOK_SECRET',
            'PAYPAL_CLIENT_ID',
            'PAYPAL_CLIENT_SECRET',
            'PAYPAL_WEBHOOK_ID',
        ];

        $missing = [];
        foreach ($required as $var) {
            if (blank(env($var))) {
                $missing[] = $var;
            }
        }

        $errors = [];
        if ($missing) {
            $errors[] = 'Missing required environment variables: '.implode(', ', $missing);
        }

        if (app()->environment('production')) {
            // Production deployments must fail fast instead of silently running unsafe defaults.
            if (config('app.debug')) {
                $errors[] = 'APP_DEBUG must be false in production.';
            }

            if (config('database.default') !== 'pgsql') {
                $errors[] = 'DB_CONNECTION must be pgsql for production ledger safety.';
            }

            if (config('queue.default') !== 'redis') {
                $errors[] = 'QUEUE_CONNECTION must be redis so payments/webhooks are processed asynchronously.';
            }

            if (config('session.secure') !== true) {
                $errors[] = 'SESSION_SECURE_COOKIE must be true in production.';
            }
        }

        if ($errors) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('Environment validation passed for production deployment.');
        return self::SUCCESS;
    }
}
