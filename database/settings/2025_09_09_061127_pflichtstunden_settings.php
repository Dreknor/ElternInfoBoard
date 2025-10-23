<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pflichtstunden.pflichtstunden_start', '08-01');
        $this->migrator->add('pflichtstunden.pflichtstunden_ende', '07-31');
        $this->migrator->add('pflichtstunden.pflichtstunden_text', 'Bitte tragen Sie hier Ihre geleisteten Pflichtstunden ein. Pro Familie sind 20 Stunden jährlich zu leisten. Vielen Dank für Ihre Unterstützung!');
        $this->migrator->add('pflichtstunden.pflichtstunden_anzahl', 20);
        $this->migrator->add('pflichtstunden.listen_autocreate', true);

    }

    public function down(): void
    {
        $this->migrator->delete('pflichtstunden.pflichtstunden_start');
        $this->migrator->delete('pflichtstunden.pflichtstunden_ende');
        $this->migrator->delete('pflichtstunden.pflichtstunden_text');
        $this->migrator->delete('pflichtstunden.pflichtstunden_anzahl');
        $this->migrator->delete('pflichtstunden.listen_autocreate');
    }
};
