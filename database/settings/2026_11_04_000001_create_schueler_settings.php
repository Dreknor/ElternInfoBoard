<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Einstellungen der Schülerkonten (docs/konzept-schuelerkonten.md §9, Paket S-01).
 * An/aus ist allein das Modul „Schülerkonten“; hier gibt es keinen zweiten Schalter.
 */
return new class extends SettingsMigration
{
    private const DEFAULTS = [
        'schueler.login_sso' => true,
        'schueler.login_benutzername' => false,
        'schueler.jit_anlage' => false,
        'schueler.ucs_provisionierung' => false,
        'schueler.freigegebene_gruppen' => [],
        'schueler.eltern_zustimmung' => false,
        'schueler.messenger_lehrkraefte' => false,
        'schueler.eltern_codes' => false,
        'schueler.loeschen_nach_tagen' => 30,
        'schueler.vertretung_abwesenheiten' => false,
    ];

    public function up(): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            if ($this->migrator->exists($key)) {
                $this->migrator->delete($key);
            }
        }
    }
};
