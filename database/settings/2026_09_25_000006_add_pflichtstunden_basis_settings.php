<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Pflichtstunden-Berechnungsgrundlage je Schule (E1/E8).
 * Defaults ergeben exakt das bisherige Verhalten (pro Familie).
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §6.2
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pflichtstunden.pflichtstunden_basis', 'family');
        $this->migrator->add('pflichtstunden.pflichtstunden_max_kinder', null);
        $this->migrator->add('pflichtstunden.pflichtstunden_geteilte_kinder', 'combined');
        $this->migrator->add('pflichtstunden.pflichtstunden_kinder_gruppen', []);
        $this->migrator->add('pflichtstunden.pflichtstunden_basis_changed_at', null);
        $this->migrator->add('pflichtstunden.pflichtstunden_basis_changed_by', null);
    }

    public function down(): void
    {
        $this->migrator->delete('pflichtstunden.pflichtstunden_basis');
        $this->migrator->delete('pflichtstunden.pflichtstunden_max_kinder');
        $this->migrator->delete('pflichtstunden.pflichtstunden_geteilte_kinder');
        $this->migrator->delete('pflichtstunden.pflichtstunden_kinder_gruppen');
        $this->migrator->delete('pflichtstunden.pflichtstunden_basis_changed_at');
        $this->migrator->delete('pflichtstunden.pflichtstunden_basis_changed_by');
    }
};
