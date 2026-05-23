# TODO-02: `UcsSetting` + Bootstrap-Config + Permissions + Helfer

> Konzept: §9 (Bootstrap-Config), §14.1 (UcsSetting), §14.2 (Settings-Migration), §14.8 (Permissions), §15.10 (CronExpression-Rule)

## Ziel

Die zentrale Laufzeit-Konfiguration der UCS-Integration als
Spatie-LaravelSettings-Klasse einführen, dazu alle notwendigen
projektweiten Helfer (Logging-Channel, Validation-Rule, Permission)
in einem Aufwasch anlegen, damit die folgenden Pakete (03 KelvinClient,
04 SyncService, 06 OIDC, 07 UI) eine stabile Basis haben.

## Scope / Anforderungen

- `App\Settings\UcsSetting` mit allen Feldern aus §14.1 inkl.
  `encrypted()`-Whitelist für `kelvin_password`.
- Settings-Migration mit Defaults aus `env()` als Bootstrap (§14.2).
- `config/ucs.php` **nur** mit nicht-konfigurierbaren Defaults für die
  Settings-Migration, nicht für Laufzeit-Lookups (§9).
- Logging-Channel `ucs` in `config/logging.php`.
- Validation-Rule `App\Rules\CronExpression`.
- Permission `manage ucs sync` im PermissionSeeder ergänzen und an
  bestehende Rollen (`Admin`, `Mitarbeiter` o.ä.) zuweisen (im
  Zweifel nur `Admin`).
- Service-Provider `App\Providers\UcsServiceProvider` registriert
  Socialite-Driver `ucs` und spiegelt `KeycloakSetting` in `services.keycloak`.

## Abhängigkeiten

- 01 (Migrationen) – wegen `users.ucs_*`-Spalten; nicht zwingend, aber
  Paket 02 sollte parallel zu 01 starten und nach 01 mergen.

## Aufgaben

- [ ] **`app/Settings/UcsSetting.php`** anlegen (Felder, `group()`,
  `encrypted()` wie §14.1).
- [ ] **`config/settings.php`** um `\App\Settings\UcsSetting::class`
  ergänzen.
- [ ] **`database/settings/2026_05_22_000000_create_ucs_settings.php`**
  anlegen (vgl. §14.2), inkl. `addEncrypted('ucs.kelvin_password', …)`.
- [ ] **`config/ucs.php`** anlegen, **nur** mit Bootstrap-Defaults und
  dem Logging-Channel-Name (vgl. §9, gekürzte Variante).
- [ ] **`config/logging.php`** um Channel `ucs` (driver `daily`,
  `storage_path('logs/ucs-sync.log')`, `days: 30`).
- [ ] **`app/Rules/CronExpression.php`** anlegen
  (Wrapper um `Cron\CronExpression::isValidExpression($value)`,
  liefert `__('validation.cron')` als Fehlermeldung).
  - In `lang/de/validation.php` Eintrag `cron => '…'` ergänzen.
- [ ] **Permission `manage ucs sync`** im PermissionSeeder (Spatie)
  registrieren und Migration/Seeder ausführen, falls Permissions in
  einem Seeder gepflegt werden.
  - Dokumentieren, welche Rollen die Permission per Default erhalten
    (Default: nur `Admin`).
- [ ] **`app/Providers/UcsServiceProvider.php`** anlegen:
  - In `register()` als Singleton nichts Spezifisches, nur Provider-Skeleton.
  - In `boot()`:
    - Socialite-Driver `ucs` registrieren (Alias auf Keycloak, Werte aus
      `KeycloakSetting`, vgl. Paket 06).
    - `config(['services.keycloak' => […]])` aus `KeycloakSetting`
      spiegeln, damit `Socialite::driver('ucs')` arbeitsfähig ist.
- [ ] **`bootstrap/providers.php`** (bzw. `config/app.php`-Providers)
  um `UcsServiceProvider::class` ergänzen.
- [ ] **`.env.example`** um die Bootstrap-Variablen ergänzen:
  `UCS_ENABLED`, `UCS_KELVIN_BASE_URL`, `UCS_KELVIN_USER`,
  `UCS_KELVIN_PASSWORD`, `UCS_KELVIN_PAGE_SIZE`, `UCS_KELVIN_TIMEOUT`,
  `UCS_KELVIN_TOKEN_TTL`, `UCS_SCHOOL`, `UCS_SYNC_ENABLED`,
  `UCS_SYNC_CRON`, `UCS_SYNC_ON_LOGIN`, `UCS_SYNC_ON_LOGIN_TIMEOUT`,
  `UCS_PURGE_AFTER_DAYS`.

## Gelingenskriterien

1. `php artisan migrate` legt die Settings-Tabellen-Einträge mit den
   Defaults aus `.env` an (oder den im Settings-Migration definierten
   Fallbacks).
2. `app(\App\Settings\UcsSetting::class)->kelvin_password` ist im DB-
   Storage verschlüsselt (manuelle Sicht ins JSON-Payload zeigt
   Base64-Crypt, nicht Klartext).
3. `validator(['x' => '30 2 * * *'], ['x' => [new \App\Rules\CronExpression]])->passes()` → `true`.
4. `validator(['x' => 'kaputt'], ['x' => [new \App\Rules\CronExpression]])->fails()` → `true`.
5. `Log::channel('ucs')->info('test')` schreibt in `storage/logs/ucs-sync-YYYY-MM-DD.log`.
6. `Permission::where('name','manage ucs sync')->exists()` → `true`
   nach Seeder-Lauf; ein User mit der Permission besteht
   `$user->can('manage ucs sync')`.
7. `Socialite::driver('ucs')` wirft **keinen** „Driver not supported“
   (sofern Paket 06 noch nicht implementiert ist, darf der Aufruf
   einen Auth-Fehler werfen, aber **keinen** „Driver not registered“).

## Out of Scope

- Tatsächlicher Login-Flow / Callback → Paket 06.
- UI-Tab → Paket 07.
- KelvinClient-Implementierung → Paket 03.

## Aufwand

M – ca. 1 Personentag.

