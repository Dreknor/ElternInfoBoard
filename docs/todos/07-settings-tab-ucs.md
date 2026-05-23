# TODO-07: Settings-Tab „UCS@school“

> Konzept: §14 (Admin-UI), §14.5 (Controller), §14.6 (Blade), §14.7 (Telemetrie), §14.8 (Permissions)

## Ziel

Vollständige Admin-UI-Integration der UCS-Anbindung im bestehenden
Settings-Bereich. Konfiguration, Verbindungstest, manuelle Sync-
Auslösung und Telemetrie-Anzeige – alles ohne `.env`-Bearbeitung.

## Scope / Anforderungen

- Neuer Tab `ucs` im bestehenden Settings-Bereich, einsortiert neben
  dem `keycloak`-Tab.
- Stil: **Bootstrap 4** (konsistent zu den bestehenden Tabs, vgl.
  `keycloak-tab.blade.php`).
- Status-Karte oben (read-only Telemetrie + Test-/Sync-Buttons).
- Formular für Kelvin-, Schul- und Sync-Parameter.
- Credentials-Felder hinter `@can('edit settings')`.
- Sync-Button hinter `permission:manage ucs sync`.
- Korrekte Validierung mit `App\Rules\CronExpression`.

## Abhängigkeiten

- 02 (`UcsSetting`, Permission `manage ucs sync`, `CronExpression`-Rule) **zwingend**.
- 03 (`KelvinClient::ping()`) **zwingend** für den Test-Button.
- 05 (`SyncUcsSchoolJob`) **zwingend** für den Sync-Button.

## Aufgaben

### Controller

- [ ] `app/Http/Controllers/SettingsController.php`:
  - In `index()` `'ucsSettings' => new UcsSetting` an die View übergeben.
  - In `update($group)` einen `case 'ucs':`-Block ergänzen, wie in
    Konzept §14.5 (aktualisierte Variante mit `$request->filled()`
    und ohne `Artisan::call('config:clear')`).
  - Methode `ucsTestConnection(KelvinClient $client): RedirectResponse`.
  - Methode `ucsRunSync(): RedirectResponse` (dispatcht den Job).

### Routing

- [ ] `routes/web.php`:
  ```php
  Route::middleware(['auth','permission:edit settings'])->group(function () {
      // settings/{group} existiert bereits → 'ucs' wird abgedeckt
      Route::post('settings/ucs/test', [SettingsController::class, 'ucsTestConnection'])
           ->name('settings.ucs.test');
      Route::post('settings/ucs/sync', [SettingsController::class, 'ucsRunSync'])
           ->name('settings.ucs.sync')
           ->middleware('permission:manage ucs sync');
  });
  ```

### Blade

- [ ] `resources/views/settings/index.blade.php`:
  - In `$navTabs` einen Eintrag `['id' => 'ucs', 'label' => 'UCS@school', 'icon' => 'fas fa-school']` einsortieren.
  - Tab-Container ergänzen:
    ```blade
    <div x-show="activeTab === 'ucs'" x-cloak class="p-6">
        @if(View::exists('settings.tabs.ucs-tab'))
            @include('settings.tabs.ucs-tab')
        @endif
    </div>
    ```
- [ ] `resources/views/settings/tabs/ucs-tab.blade.php` neu nach
  Konzept §14.6 mit:
  - Status-Karte (Letzter Sync, Status-Badge, „stale“-Hinweis bei
    abgelaufenem Lock, Buttons).
  - Master-Schalter (`enabled`).
  - Block „Kelvin REST API“: Basis-URL, Schule, Service-Account-User,
    Service-Account-Password (`@can('edit settings')`), Page-Size,
    Timeout, Token-TTL.
  - Block „Synchronisation“: `sync_enabled`, `sync_cron`,
    `on_login_fallback`, `on_login_timeout`, `purge_after_days`.
  - Warnung „Änderung der Schule oder Basis-URL kann verwaiste
    Datensätze erzeugen“.
  - Sync-Button (`route('settings.ucs.sync')`) per
    `@can('manage ucs sync')` einkapseln.

### Telemetrie-Anzeige

- [ ] „Stale“-Status: Wenn `last_sync_status='running'` und
  `Cache::missing('ucs.sync.running_lock')`, zeige Badge „stale (Lock abgelaufen)“.

### Übersetzungen

- [ ] Aktuell hartcodiert deutsch (konsistent zu Bestand), keine
  zusätzliche Lang-Datei nötig (§15.9).

## Gelingenskriterien

1. Auf `/settings` ist der Tab „UCS@school“ sichtbar und navigierbar.
2. Formular speichert die Werte korrekt; Passwort bleibt erhalten,
   wenn das Feld leer abgeschickt wird.
3. Manuell falsches `sync_cron` (`'kaputt'`) wird vom FormRequest
   abgelehnt (`CronExpression`-Rule).
4. Test-Button mit gültigen Credentials → grüne Flash-Message
   „Verbindung OK. Erreichte Schulen: N“.
5. Test-Button mit falschen Credentials → rote Flash-Message mit
   Exception-Message.
6. Sync-Button → Job liegt in der Queue (`Bus::fake()`-Test),
   Flash-Message „Sync wurde in die Warteschlange gestellt“.
7. User ohne `manage ucs sync` sieht den Sync-Button **nicht** und
   bekommt bei direktem POST 403.
8. User ohne `edit settings` sieht die Credentials-Felder **nicht** und
   stattdessen den Lock-Hinweis aus §14.6.
9. Status-Karte zeigt korrekt `last_sync_at`, `last_sync_status` und
   die Counter (in einem fixierten Setting-State testbar).

## Out of Scope

- Linking-Kandidaten-Liste → Paket 08 (eigene UI-Sektion).
- Tailwind-Migration aller Settings-Tabs (separates Projekt, §15.9).

## Aufwand

M – ca. 1–2 Personentage.

