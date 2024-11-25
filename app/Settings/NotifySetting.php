<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NotifySetting extends Settings
{

    /**
     * The Hour to send the information mail to the users
     */
    public int $hour_send_information_mail;

    public int $weekday_send_information_mail;

    public int $hour_send_reminder_mail;

    public int $krankmeldungen_report_hour;
    public int $krankmeldungen_report_minute;

    public int $schickzeiten_report_hour;
    public int $schickzeiten_report_weekday;

    public static function group(): string
    {
        return 'notify_setting';
    }

}
