<?php

namespace App\Console;

use App\Support\SystemStatus;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Heartbeat: Admin → Dashboard → System status shows whether the server cron is calling schedule:run
        $schedule->call(fn () => Cache::forever(SystemStatus::HEARTBEAT, now()->toIso8601String()))->everyMinute()->name('scheduler-heartbeat');

        // Shared hosting without root/Supervisor (cPanel): everything runs inside the per-minute
        // `schedule:run` process itself. A scheduled ->command() starts a new PHP process with the binary
        // the cron used, which on cPanel is often not the CLI PHP the app needs and fails silently, while
        // the heartbeat above still reports the cron as fine. Artisan::call() needs no new process.
        $schedule->call(fn () => Artisan::call('billing:daily'))->name('billing-daily')->dailyAt('08:00')->withoutOverlapping(60);
        $schedule->call(fn () => Artisan::call('queue:work', ['connection' => 'database', '--stop-when-empty' => true, '--tries' => 3, '--max-time' => 40]))
            ->name('queue-worker')->everyMinute()->withoutOverlapping(5);
        $schedule->call(fn () => Artisan::call('billing:webhooks', ['--limit' => 50]))->name('payment-webhooks')->everyMinute()->withoutOverlapping(5);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
