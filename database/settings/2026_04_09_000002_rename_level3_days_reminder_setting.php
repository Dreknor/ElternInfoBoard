<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Benennt `reminder.level3_days_after_deadline` um in `reminder.level3_days_before_deadline`
 * und setzt den Standardwert auf 0 (= am Fristtag selbst).
 *
 * Hintergrund: Stufe 3 feuert nun VOR dem Fristablauf (wie Stufen 1 und 2),
 * nicht mehr danach. Bei 0 Tagen wird am Fristtag selbst erinnert.
 */
class RenameLevel3DaysReminderSetting extends SettingsMigration
{
    public function up(): void
    {
        // Nur umbenennen wenn der alte Schlüssel noch existiert (Produktions-Upgrade).
        // In einer frischen Installation ist der Schlüssel bereits korrekt gesetzt.
        $oldExists = \DB::table('settings')
            ->where('group', 'reminder')
            ->where('name', 'level3_days_after_deadline')
            ->exists();

        if ($oldExists) {
            $this->migrator->delete('reminder.level3_days_after_deadline');
            $this->migrator->add('reminder.level3_days_before_deadline', 0);
        }
    }

    public function down(): void
    {
        $this->migrator->delete('reminder.level3_days_before_deadline');
        $this->migrator->add('reminder.level3_days_after_deadline', 1);
    }
}


