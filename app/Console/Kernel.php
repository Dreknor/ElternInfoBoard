<?php

namespace App\Console;

use App\Http\Controllers\GroupsController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Spatie\Permission\Models\Role;

/**
 *
 */
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $AdminRole = Role::where('name', 'Administrator')->orWhere('name', 'Admin')->first();
        $admin = $AdminRole->users()->first();
        if (isset($admin) and $admin->email != '') {
            $email = $admin->email;
        } else {
            $email = config('mail.from.address');
        }


        $schedule->call('App\Http\Controllers\NachrichtenController@emailDaily')->dailyAt('17:00');

        $schedule->call('App\Http\Controllers\KrankmeldungenController@dailyReport')->weekdays()->at('08:30');
        //$schedule->call('App\Http\Controllers\FeedbackController@dailyReport')->weekdays()->at('08:30');
        $schedule->call('App\Http\Controllers\RueckmeldungenController@sendErinnerung')->dailyAt('17:00');

        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:00');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:05');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:10');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:15');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:20');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:50');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn(5, '17:55');

        $schedule->call('App\Http\Controllers\SchickzeitenController@sendReminder')->weeklyOn(5, '18:00');

        $schedule->call('App\Http\Controllers\GroupsController@deletePrivateGroups')->yearlyOn(7, 31, '00:00');
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
