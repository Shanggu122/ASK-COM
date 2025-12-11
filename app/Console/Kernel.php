<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // 1-hour prior rolling reminder: checks every 5 minutes for sessions starting within the next hour
        $schedule->command("consultations:remind-upcoming")->everyMinute()->timezone("Asia/Manila");
        // Daily prune of old login attempts
        $schedule->command("login-attempts:prune")->daily()->timezone("Asia/Manila");
        $schedule
            ->command("academic-terms:process-rollover")
            ->dailyAt("00:30")
            ->timezone("Asia/Manila");
        // Legacy 7 AM daily batch kept commented for reference
        // $schedule->command('consultations:remind-today')->dailyAt('07:00')->timezone('Asia/Manila');
    }

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\BackfillRescheduleReasons::class,
        \App\Console\Commands\ProcessTermRollover::class,
    ];

    protected function commands(): void
    {
        $this->load(__DIR__ . "/Commands");
        require base_path("routes/console.php");
    }
}
