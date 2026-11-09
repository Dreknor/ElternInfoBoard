<?php

namespace App\Support;

use App\Model\Module;

/**
 * Zentrale Abfrage für Schülerkonten (docs/konzept-schuelerkonten.md §1.1).
 *
 * Jede schülerbezogene Anzeige (Felder, Reiter, Badges, API-Felder) und jede Schülerfunktion
 * prüft `enabled()`. Ist das Modul aus oder für die Instanz gesperrt, gibt es keinen Hinweis
 * auf Schülerkonten. Ausgenommen ist nur der Schutz beim SSO-Login (S-02), der immer gilt.
 */
final class StudentAccounts
{
    /** Name des Moduls (`settings_modules.setting`), instanzgesperrt in `config/modules.php`. */
    public const MODULE = 'Schülerkonten';

    /** Recht: Beiträge und Termine auch für Schüler sichtbar machen. */
    public const PERMISSION_CREATE_POSTS = 'create student posts';

    /** Werte für `posts.zielgruppe` und `termine.zielgruppe`. */
    public const ZIELGRUPPE_ELTERN = 'eltern';

    public const ZIELGRUPPE_ELTERN_SCHUELER = 'eltern_schueler';

    public const ZIELGRUPPE_SCHUELER = 'schueler';

    public const ZIELGRUPPEN = [
        self::ZIELGRUPPE_ELTERN,
        self::ZIELGRUPPE_ELTERN_SCHUELER,
        self::ZIELGRUPPE_SCHUELER,
    ];

    /** Zielgruppen, die Eltern sehen (`schueler` sehen Eltern nie, auch bei ausgeschaltetem Modul). */
    public const ZIELGRUPPEN_FUER_ELTERN = [
        self::ZIELGRUPPE_ELTERN,
        self::ZIELGRUPPE_ELTERN_SCHUELER,
    ];

    /** Sanctum-Fähigkeit der App-Tokens von Schülerkonten (§8). */
    public const TOKEN_ABILITY = 'student';

    /** Laufzeit der App-Tokens von Schülerkonten in Tagen (§6.2). */
    public const TOKEN_DAYS = 30;

    /** Ist die Funktion auf dieser Instanz freigeschaltet und als Modul aktiv? */
    public static function enabled(): bool
    {
        if (Module::isLockedSetting(self::MODULE)) {
            return false;
        }

        // Je Anfrage einmal aus der Datenbank (wird bei Änderungen am Modul verworfen, siehe Module::booted)
        $app = spl_object_id(app());
        // In Konsole/Queue-Workern (langlebige Prozesse) nicht zwischenspeichern, außer in Tests
        if (self::$active === null || self::$activeFor !== $app || (app()->runningInConsole() && ! app()->runningUnitTests())) {
            $module = Module::query()->where('setting', self::MODULE)->first();
            $active = $module?->options['active'] ?? false;
            self::$active = $active === true || $active === 1 || $active === '1';
            self::$activeFor = $app;
        }

        return self::$active;
    }

    private static ?bool $active = null;

    private static ?int $activeFor = null;

    /** Zwischengespeicherten Modulstatus verwerfen. */
    public static function flush(): void
    {
        self::$active = null;
    }

    /** Login mit Benutzername für Schüler möglich (Modul aktiv und Einstellung an)? */
    public static function usernameLoginEnabled(): bool
    {
        if (! self::enabled()) {
            return false;
        }

        try {
            return (bool) app(\App\Settings\SchuelerSetting::class)->login_benutzername;
        } catch (\Throwable) {
            return false;
        }
    }
}
