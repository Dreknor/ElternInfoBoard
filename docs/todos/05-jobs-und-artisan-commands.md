# TODO-05: Jobs, Artisan-Commands & Scheduler

> Konzept: §5.1 (Komponenten), §5.4 (Scheduling), §7.3 (Purge), §11 (Roll-out)

## Ziel

Die `UcsSyncService`-Logik aus Paket 04 als asynchronen Job und als
CLI-Befehle bereitstellen, samt Scheduler-Eintrag und Hard-Purge-Job
für verwaiste Klassen-Gruppen.

## Scope / Anforderungen

- `App\Jobs\SyncUcsSchoolJob` (queueable, idempotent, `onOneServer`,
  `withoutOverlapping`).
- Artisan-Commands für manuelle Auslösung und Wartung.
- Scheduler-Eintrag liest Cron-Ausdruck aus `UcsSetting::sync_cron`.
- Sauberes Logging in Channel `ucs`, exit codes
  (0=ok, 1=Fehler, 2=disabled).

## Abhängigkeiten

- 02 (`UcsSetting`, Logging-Channel) **zwingend**.
- 03 (`KelvinClient`) **zwingend**.
- 04 (`UcsSyncService`) **zwingend**.

## Aufgaben

### Jobs

- [ ] **`app/Jobs/SyncUcsSchoolJob.php`**
  - `public int $timeout = 900;`
  - `public int $tries = 1;`
  - Konstruktor **ohne** Parameter (Schule kommt aus `UcsSetting`).
  - `handle(UcsSyncService $svc)`:
    - Abbruch wenn `!enabled || !sync_enabled` (return + Log).
    - `$svc->run()` aufrufen.
  - `failed(\Throwable $e)`: `UcsSetting::last_sync_status='failed'`
    + Sentry-Capture.

### Artisan-Commands

- [ ] **`ucs:ping`** (`App\Console\Commands\UcsPing`)
  - Ruft `KelvinClient::ping()` auf.
  - Output: Anzahl Schulen, erste Schul-Namen, HTTP-Latency.
  - Exit 0 / 1.
- [ ] **`sync:ucs-parents [--dry-run]`** (`App\Console\Commands\SyncUcsParents`)
  - Dispatcht `SyncUcsSchoolJob` **synchron** im CLI-Kontext
    (`SyncUcsSchoolJob::dispatchSync()`), damit die Konsole das
    Ergebnis sehen kann.
  - Mit `--dry-run`: `app(UcsSyncService::class)->run(dryRun: true)`
    direkt aufrufen und Counter ausgeben.
  - Exit 0 / 1 / 2 (disabled).
- [ ] **`ucs:purge-stale-classes`** (`App\Console\Commands\UcsPurgeStaleClasses`)
  - Liest `UcsSetting::purge_after_days` (Default 14).
  - Iteriert SoftDeleted Klassen-Gruppen mit
    `ucs_source='kelvin'` und `deleted_at < now()->subDays($days)`.
  - Pro Gruppe in `DB::transaction()` die explizite Kaskade aus §7.3
    ausführen (Posts, Conversations, Reminders, Pivots, dann
    `forceDelete()`). Welche Relationen das genau sind, ist Teil von
    Paket 10 (Inventur, §15.3) – für jetzt: alle, die im Konzept
    aufgelistet sind, plus eine Liste, die der Entwickler bestätigt.
  - Output: pro Gruppe „purged group_id=X (with N posts, M conversations, …)“.
- [ ] **`ucs:link-child {child_id} {ucs_username}`** (`App\Console\Commands\UcsLinkChild`)
  - Schreibt `children.ucs_username` und ggf. `ucs_uuid` auf den
    lokalen Datensatz, löscht den passenden
    `ucs_link_candidates`-Eintrag (mit `confirmed_at=now()`,
    `confirmed_by=null` bei CLI).
  - Validiert, dass das Kind in der lokalen DB existiert und
    `ucs_username` noch leer ist.
  - Idempotent: zweimaliger Aufruf ändert nichts mehr.

### Scheduler

- [ ] **`app/Console/Kernel.php` (oder `routes/console.php`)** ergänzen:
  ```php
  $schedule->job(new SyncUcsSchoolJob)
      ->cron(app(UcsSetting::class)->sync_cron ?: '30 2 * * *')
      ->when(fn () => app(UcsSetting::class)->enabled
                   && app(UcsSetting::class)->sync_enabled)
      ->onOneServer()
      ->withoutOverlapping();

  $schedule->command('ucs:purge-stale-classes')
      ->weeklyOn(0, '03:00')
      ->when(fn () => app(UcsSetting::class)->enabled)
      ->onOneServer();
  ```

### Queue-Konfiguration

- [ ] Default-Queue-Name dokumentieren (`default`), oder eigene Queue
  `ucs` erlauben, falls dafür ein eigener Worker gewünscht ist
  (`SyncUcsSchoolJob::dispatch()->onQueue('ucs')`).

## Gelingenskriterien

1. `php artisan ucs:ping` gegen einen fake-MockKelvinClient liefert
   Exit 0 und schreibt eine Status-Zeile.
2. `php artisan sync:ucs-parents --dry-run` schreibt **null** Mutations
   und liefert einen Counter-Report auf STDOUT.
3. `php artisan sync:ucs-parents` mit `UcsSetting::enabled=false`
   liefert Exit 2 mit Hinweis-Message; keine Mutation.
4. `php artisan schedule:list` zeigt beide UCS-Tasks.
5. `Bus::fake(); SyncUcsSchoolJob::dispatch();` → Job ist auf Queue
   `default` einplanbar, `$timeout=900` ist gesetzt.
6. `php artisan ucs:purge-stale-classes` löscht in einer Testfixture
   **nur** Klassen-Gruppen, die SoftDeleted + älter als
   `purge_after_days` sind. Lokale (`ucs_source='local'`) Gruppen
   bleiben unberührt.
7. `php artisan ucs:link-child 42 max.mueller` schreibt
   `children.ucs_username='max.mueller'` und entfernt den passenden
   `ucs_link_candidates`-Datensatz.
8. Doppelter Aufruf von `ucs:link-child` ändert nichts, Exit 0,
   Hinweis-Message.

## Out of Scope

- UI-Auslöser (Button) → Paket 07.
- OIDC-/Login-Auslöser → Paket 06.
- Linking-UI → Paket 08.

## Aufwand

M – ca. 1–2 Personentage.

