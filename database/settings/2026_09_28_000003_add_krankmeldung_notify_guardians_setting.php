<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Krankmeldung: weitere Berechtigte des Kindes informieren (Spezifikation 2.4).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('notify_setting.krankmeldung_notify_guardians')) {
            $this->migrator->add('notify_setting.krankmeldung_notify_guardians', true);
        }
    }
};
