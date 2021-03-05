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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();

        //$schedule->call('App\Http\Controllers\NachrichtenController@emailDaily')->dailyAt('10:00');
        //$schedule->call('App\Http\Controllers\NachrichtenController@emailDaily')->dailyAt('13:00');
        $schedule->call('App\Http\Controllers\NachrichtenController@emailDaily')->dailyAt('17:00');
        $schedule->call('App\Http\Controllers\KrankmeldungenController@dailyReport')->dailyAt('08:30');
        $schedule->call('App\Http\Controllers\RueckmeldungenController@sendErinnerung')->dailyAt('17:00');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:00');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:15');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:30');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:45');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '18:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
