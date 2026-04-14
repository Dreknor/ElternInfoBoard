<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateReminderSettings extends SettingsMigration
{
    public function up(): void
    {
        // Stufe 1: Sanfte In-App-Erinnerung
        $this->migrator->add('reminder.level1_active', true);
        $this->migrator->add('reminder.level1_days_before_deadline', 5);
        $this->migrator->add('reminder.level1_in_app', true);
        $this->migrator->add('reminder.level1_email', false);
        $this->migrator->add('reminder.level1_push', false);

        // Stufe 2: Dringendere Erinnerung (E-Mail + Push)
        $this->migrator->add('reminder.level2_active', true);
        $this->migrator->add('reminder.level2_days_before_deadline', 2);
        $this->migrator->add('reminder.level2_in_app', true);
        $this->migrator->add('reminder.level2_email', true);
        $this->migrator->add('reminder.level2_push', true);

        // Stufe 3: Letzte Erinnerung + Eskalation (am Fristtag, d.h. 0 Tage vor Ablauf)
        $this->migrator->add('reminder.level3_active', true);
        $this->migrator->add('reminder.level3_days_before_deadline', 0);  // 0 = am Fristtag selbst
        $this->migrator->add('reminder.level3_in_app', true);
        $this->migrator->add('reminder.level3_email', true);
        $this->migrator->add('reminder.level3_push', true);
        $this->migrator->add('reminder.level3_escalate_to_author', true);

        // Allgemein
        $this->migrator->add('reminder.send_time', '08:00');
        $this->migrator->add('reminder.include_read_receipts', true);
        $this->migrator->add('reminder.include_rueckmeldungen', true);
        $this->migrator->add('reminder.include_attendance_queries', true);
    }

    public function down(): void
    {
        $this->migrator->delete('reminder.level1_active');
        $this->migrator->delete('reminder.level1_days_before_deadline');
        $this->migrator->delete('reminder.level1_in_app');
        $this->migrator->delete('reminder.level1_email');
        $this->migrator->delete('reminder.level1_push');

        $this->migrator->delete('reminder.level2_active');
        $this->migrator->delete('reminder.level2_days_before_deadline');
        $this->migrator->delete('reminder.level2_in_app');
        $this->migrator->delete('reminder.level2_email');
        $this->migrator->delete('reminder.level2_push');

        $this->migrator->delete('reminder.level3_active');
        $this->migrator->delete('reminder.level3_days_before_deadline');
        $this->migrator->delete('reminder.level3_in_app');
        $this->migrator->delete('reminder.level3_email');
        $this->migrator->delete('reminder.level3_push');
        $this->migrator->delete('reminder.level3_escalate_to_author');

        $this->migrator->delete('reminder.send_time');
        $this->migrator->delete('reminder.include_read_receipts');
        $this->migrator->delete('reminder.include_rueckmeldungen');
        $this->migrator->delete('reminder.include_attendance_queries');
    }
}

