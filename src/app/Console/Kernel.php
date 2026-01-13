<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\ShowUsersTable::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Evaluate expired predictions every hour
        $schedule->command('predictions:evaluate')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Optional: Run more frequently during market hours (9 AM - 4 PM EST)
        // $schedule->command('predictions:evaluate')
        //          ->cron('0 9-16 * * 1-5') // Every hour from 9 AM to 4 PM, Mon-Fri
        //          ->timezone('America/New_York')
        //          ->withoutOverlapping()
        //          ->runInBackground();
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