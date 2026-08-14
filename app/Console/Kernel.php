<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // Backup Cleanup
        $schedule->command('backup:clean')->daily()->at('01:00');

        // Daily chase for renewals, expiries, holds and low cloth counts.
        $schedule->command('renewal:send-notifications')->dailyAt('09:00')->withoutOverlapping();

        // Weekly sweep that parks subscriptions on hold once they are a week
        // past renewal. Runs after the daily reminder so a customer is warned
        // before their cleaning stops.
        $schedule->command('orders:auto-hold')->weeklyOn(1, '09:30')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
