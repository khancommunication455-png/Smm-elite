<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\SyncOrderStatus::class,
        Commands\ValidateEnvironment::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Auto-sync order statuses from API providers every 5 minutes
        $schedule->command('orders:sync')->everyFiveMinutes();

        // Refresh PKR exchange rate daily at midnight
        $schedule->call(fn() => \App\Services\ExchangeRateService::refresh())->dailyAt('00:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
