<?php

namespace App\Console;

use App\Model\Module;
use App\Settings\NotifySetting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $notifySetting = new NotifySetting;

        // Kinder einchecken
        $careModule = Module::where('setting', 'Anwesenheitsliste')->first();
        if ($careModule->options['active'] == 1) {
            $schedule->call('App\Http\Controllers\Anwesenheit\CareController@dailyCheckIn')->weekdays()->at('08:30');
        }

        $schedule->call('App\Http\Controllers\NotificationController@clean_up')->dailyAt('00:00');
        $schedule->call('App\Http\Controllers\CleanupController@clean_up')->daily()->at('01:00');

        $schedule->call('App\Http\Controllers\NachrichtenController@emailDaily')->dailyAt($notifySetting->hour_send_information_mail.':00');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':00');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':05');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':10');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':15');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':20');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':50');
        $schedule->call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':55');

        $schedule->call('App\Http\Controllers\RueckmeldungenController@sendErinnerung')->dailyAt($notifySetting->hour_send_reminder_mail.':00');
        $schedule->call('App\Http\Controllers\ReadReceiptsController@remind')->dailyAt($notifySetting->hour_send_reminder_mail.':00');
        $schedule->call('App\Http\Controllers\ReadReceiptsController@sendFinalReminder')->hourly();

        $schedule->call('App\Http\Controllers\KrankmeldungenController@dailyReport')->weekdays()->at($notifySetting->krankmeldungen_report_hour.':'.$notifySetting->krankmeldungen_report_minute);

        $schedule->call('App\Http\Controllers\SchickzeitenController@sendReminder')->weeklyOn($notifySetting->schickzeiten_report_weekday, $notifySetting->schickzeiten_report_hour.':00');

        $schedule->call('App\Http\Controllers\GroupsController@deletePrivateGroups')->yearlyOn(7, 31, '00:00');

        $schedule->call('App\Http\Controllers\SchickzeitenController@copyWeeklySchickzeitenToNextWeek')->weeklyOn(6, '00:00');

        // Elternrat Event Erinnerungen - stündlich prüfen
        $schedule->call('App\Http\Controllers\ElternratEventController@sendReminders')->hourly();

        // Alte Logs automatisch löschen (alle 7 Tage, Logs älter als 90 Tage)
        $schedule->command('logs:cleanup --days=90')->weeklyOn(1, '02:00');

        // Wenn die Queue nicht über Supervisor läuft, dann wird sie hier gestartet
        // Default ist die Queue über Supervisor zu starten
        if (config('queue.use_cronjob')) {
            $schedule->command('queue:work --stop-when-empty')->withoutOverlapping();
        }
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
