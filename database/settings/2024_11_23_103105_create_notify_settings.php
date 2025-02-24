<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('notify_setting.hour_send_information_mail', 17);
        $this->migrator->add('notify_setting.weekday_send_information_mail', 5);
        $this->migrator->add('notify_setting.hour_send_reminder_mail', 18);
        $this->migrator->add('notify_setting.krankmeldungen_report_hour', 8);
        $this->migrator->add('notify_setting.krankmeldungen_report_minute', 30);
        $this->migrator->add('notify_setting.schickzeiten_report_hour', 17);
        $this->migrator->add('notify_setting.schickzeiten_report_weekday', 5);

    }
};
