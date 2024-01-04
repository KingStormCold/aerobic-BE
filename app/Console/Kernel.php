<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        \App\Console\Commands\SendEmail::class, //command SendEmail
    ];
    // /**
    //  * Register the commands for the application.
    //  */
    protected function schedule(Schedule $schedule)
    {
        Log::info(' gửi sau 1 phút ');
        $schedule->command('send:email')->everyMinute();
    }
}
