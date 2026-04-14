<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ReminderSetting extends Settings
{
    // Stufe 1: Sanfte In-App-Erinnerung
    public bool $level1_active;
    public int $level1_days_before_deadline;
    public bool $level1_in_app;
    public bool $level1_email;
    public bool $level1_push;

    // Stufe 2: Dringendere Erinnerung (E-Mail + Push)
    public bool $level2_active;
    public int $level2_days_before_deadline;
    public bool $level2_in_app;
    public bool $level2_email;
    public bool $level2_push;

    // Stufe 3: Letzte Erinnerung + Eskalation (am Fristtag oder N Tage davor)
    public bool $level3_active;
    public int $level3_days_before_deadline;
    public bool $level3_in_app;
    public bool $level3_email;
    public bool $level3_push;
    public bool $level3_escalate_to_author;

    // Allgemein
    public string $send_time;
    public bool $include_read_receipts;
    public bool $include_rueckmeldungen;
    public bool $include_attendance_queries;

    public static function group(): string
    {
        return 'reminder';
    }
}

