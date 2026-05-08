<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateEnvironment extends Command
{
    protected $signature = 'env:validate';
    protected $description = 'Validate required environment variables';

    public function handle()
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
            'MAIL_MAILER',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME',
        ];

        $missing = [];
        foreach ($required as $var) {
            if (!env($var)) {
                $missing[] = $var;
            }
        }

        if ($missing) {
            $this->error('Missing required environment variables: ' . implode(', ', $missing));
            return 1;
        }

        $this->info('All required environment variables are set.');
        return 0;
    }
}