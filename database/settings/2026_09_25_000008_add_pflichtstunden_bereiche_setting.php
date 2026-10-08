<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * pflichtstunden_bereiche hatte nur einen Default im Settings-Objekt, aber
 * keinen Datensatz – Speichern des Pflichtstunden-Tabs schlug dadurch fehl.
 * Auf Instanzen, auf denen der Wert bereits existiert, passiert nichts.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('pflichtstunden.pflichtstunden_bereiche')) {
            $this->migrator->add('pflichtstunden.pflichtstunden_bereiche', []);
        }
    }

    public function down(): void
    {
        // bewusst leer: der Wert kann schon vor dieser Migration existiert haben
    }
};
