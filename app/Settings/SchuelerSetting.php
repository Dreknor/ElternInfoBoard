<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Einstellungen der Schülerkonten (docs/konzept-schuelerkonten.md §9).
 * Ob die Funktion an ist, entscheidet allein das Modul: App\Support\StudentAccounts::enabled().
 */
class SchuelerSetting extends Settings
{
    /** Login über SSO (UCS-OIDC oder Keycloak) erlauben. */
    public bool $login_sso;

    /** Login mit Benutzername + Passwort (ohne SSO). */
    public bool $login_benutzername;

    /** Konto beim ersten SSO-Login anlegen (nur für freigegebene Gruppen). */
    public bool $jit_anlage;

    /** Konten per UCS-Sync anlegen (nur für freigegebene Gruppen). */
    public bool $ucs_provisionierung;

    /** @var list<int> Gruppen/Klassen, für die Konten automatisch entstehen dürfen (JIT, UCS). */
    public array $freigegebene_gruppen;

    /** Konto erst aktiv, wenn ein Elternteil zugestimmt hat. */
    public bool $eltern_zustimmung;

    /** Schüler dürfen Lehrkräften ihrer Gruppen schreiben. */
    public bool $messenger_lehrkraefte;

    /** Eltern dürfen Einmal-Codes erzeugen. */
    public bool $eltern_codes;

    /** Löschfrist in Tagen nach Deaktivierung. */
    public int $loeschen_nach_tagen;

    /** Abwesenheiten von Lehrkräften auch Schülern zeigen. */
    public bool $vertretung_abwesenheiten;

    public static function group(): string
    {
        return 'schueler';
    }
}
