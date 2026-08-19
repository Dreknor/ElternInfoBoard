<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pflichtstunden.pflichtstunden_anzahl_ermaessigt', 10);
        $this->migrator->add('pflichtstunden.pflichtstunden_betrag_ermaessigt', 12.50);
        $this->migrator->add('pflichtstunden.konto_uebertrag_aktiv', false);
        $this->migrator->add('pflichtstunden.konto_uebertrag_max_stunden', null);
    }

    public function down(): void
    {
        $this->migrator->delete('pflichtstunden.pflichtstunden_anzahl_ermaessigt');
        $this->migrator->delete('pflichtstunden.pflichtstunden_betrag_ermaessigt');
        $this->migrator->delete('pflichtstunden.konto_uebertrag_aktiv');
        $this->migrator->delete('pflichtstunden.konto_uebertrag_max_stunden');
    }
};
