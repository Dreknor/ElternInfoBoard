<?php

use App\Model\Module;
use App\Settings\NotifySetting;
use App\Settings\ReminderSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:create-password-resets', function () {
    if (!Schema::hasTable('password_resets')) {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        $this->info('Tabelle "password_resets" wurde erfolgreich erstellt.');
    } else {
        $this->info('Tabelle "password_resets" existiert bereits.');
    }
})->purpose('Create password_resets table if it does not exist');

// Only load settings and modules if tables exist (not during migrations)
try {
    if (Schema::hasTable('settings') && Schema::hasTable('settings_modules')) {
        $notifySetting = new NotifySetting;

        // Kinder einchecken
        $careModule = Module::where('setting', 'Anwesenheitsliste')->first();
        if ($careModule && $careModule->options['active'] == 1) {
            Schedule::call('App\Http\Controllers\Anwesenheit\CareController@dailyCheckIn')->weekdays()->at('08:30');
        }

        Schedule::call('App\Http\Controllers\NotificationController@clean_up')->dailyAt('00:00');
        Schedule::call('App\Http\Controllers\CleanupController@clean_up')->daily()->at('01:00');

        Schedule::call('App\Http\Controllers\NachrichtenController@emailDaily')->dailyAt($notifySetting->hour_send_information_mail.':00');
        Schedule::call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':00');
        Schedule::call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':05');
        Schedule::call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':10');
        Schedule::call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':15');
        Schedule::call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':20');
        Schedule::call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':50');
        Schedule::call('App\Http\Controllers\NachrichtenController@email')->weeklyOn($notifySetting->weekday_send_information_mail, $notifySetting->hour_send_information_mail.':55');

        // DEAKTIVIERT: Wird jetzt durch ProcessRemindersJob (Feature 3) abgedeckt
        // Schedule::call('App\Http\Controllers\RueckmeldungenController@sendErinnerung')->dailyAt($notifySetting->hour_send_reminder_mail.':00');
        // Schedule::call('App\Http\Controllers\ReadReceiptsController@remind')->dailyAt($notifySetting->hour_send_reminder_mail.':00');
        // Schedule::call('App\Http\Controllers\ReadReceiptsController@sendFinalReminder')->hourly();

        Schedule::call('App\Http\Controllers\KrankmeldungenController@dailyReport')->weekdays()->at($notifySetting->krankmeldungen_report_hour.':'.$notifySetting->krankmeldungen_report_minute);

        Schedule::call('App\Http\Controllers\SchickzeitenController@sendReminder')->weeklyOn($notifySetting->schickzeiten_report_weekday, $notifySetting->schickzeiten_report_hour.':00');

        Schedule::call('App\Http\Controllers\GroupsController@deletePrivateGroups')->yearlyOn(7, 31, '00:00');

        Schedule::call('App\Http\Controllers\SchickzeitenController@copyWeeklySchickzeitenToNextWeek')->weeklyOn(6, '00:00');

        // Anwesenheitsabfragen-Erinnerungen – DEAKTIVIERT: wird jetzt durch ProcessRemindersJob (Feature 3, Teil C) abgedeckt
        // Schedule::job(new \App\Jobs\SendAttendanceQueryReminderJob)->dailyAt('08:00');

        // ── Neues Erinnerungssystem (Feature 3) ──────────────────
        // Unified Reminder Pipeline: Rückmeldungen + ReadReceipts + Anwesenheitsabfragen
        try {
            $reminderSetting = new ReminderSetting;
            Schedule::job(new \App\Jobs\ProcessRemindersJob)->dailyAt($reminderSetting->send_time);
        } catch (\Exception $e) {
            // Settings-Tabelle noch nicht migriert – Job wird nicht eingeplant
        }

        // Elternrat Event Erinnerungen - stündlich prüfen
        Schedule::call('App\Http\Controllers\ElternratEventController@sendReminders')->hourly();

        // Alte Logs automatisch löschen (alle 7 Tage, Logs älter als 90 Tage)
        Schedule::command('logs:cleanup --days=90')->weeklyOn(1, '02:00');

        // Alte CheckIns automatisch löschen (täglich, CheckIns älter als 3 Monate)
        Schedule::command('checkins:cleanup --months=3')->dailyAt('03:00');

        // Alte Schickzeiten mit spezifischem Datum löschen (täglich, älter als 2 Wochen)
        Schedule::command('schickzeiten:cleanup --weeks=2')->dailyAt('03:30');

        // Schickzeiten von Kindern löschen, die nicht mehr im Care-Modul sind
        Schedule::command('schickzeiten:cleanup-non-care')->dailyAt('03:35');

        // Alte Child Notices automatisch löschen (täglich, Child Notices älter als 3 Monate)
        Schedule::command('child-notices:cleanup --months=3')->dailyAt('03:45');

        // ── Datenschutz-Cleanups (DSGVO-Konzept Apr. 2026) ────────
        // Audit-Trail (IP/User-Agent) auf 12 Monate begrenzen
        Schedule::command('audits:cleanup --days=365')->weeklyOn(1, '02:30');
        // Reminder-Logs auf 12 Monate begrenzen
        Schedule::command('reminder-logs:cleanup --days=365')->dailyAt('02:45');
        // Lesebestätigungen auf 12 Monate / verwaiste entfernen
        Schedule::command('read-receipts:cleanup --days=365')->dailyAt('02:50');
        // Abgelaufene Sanctum-Tokens entfernen
        Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('02:55');
        // Krankmeldungen-Cleanup (täglich + zusätzlich harter Run am 1. 8.)
        Schedule::command('krankmeldungen:cleanup')->dailyAt('01:15');
        Schedule::command('krankmeldungen:cleanup')->yearlyOn(8, 1, '03:00');
        // Messenger: Schuljahresend-Löschung am 1. 8.
        Schedule::command('messenger:cleanup-school-year')->yearlyOn(8, 1, '02:15');
        // User-Cleanup: Force-Delete soft-gelöschter User nach 90 Tagen
        Schedule::command('users:cleanup --purge-days=90')->dailyAt('04:00');
        // Monatlicher Inaktivitätsbericht an Administratoren
        Schedule::command('users:cleanup --report')->monthlyOn(1, '09:00');

        // ── Feature 2: Messenger-Jobs ──────────────────────────────
        $messengerModule = Module::where('setting', 'Eltern-Nachrichten')->first();
        if ($messengerModule && ($messengerModule->options['active'] ?? false)) {
            // Alte Nachrichten bereinigen (täglich 02:00)
            Schedule::job(new \App\Jobs\CleanupOldMessagesJob)->dailyAt('02:00');
            // Wöchentlicher Report ungelöster Meldungen (montags 09:00)
            Schedule::job(new \App\Jobs\SendUnresolvedReportsDigest)->weeklyOn(1, '09:00');
        }
    }
} catch (\Exception $e) {
    // Silently catch exceptions during migration/setup
}

// Wenn die Queue nicht über Supervisor läuft, dann wird sie hier gestartet
// Default ist die Queue über Supervisor zu starten
if (config('queue.use_cronjob')) {
    Schedule::command('queue:work --stop-when-empty')->withoutOverlapping();
}
