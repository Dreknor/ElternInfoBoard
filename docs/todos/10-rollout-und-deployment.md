# TODO-10: Roll-out, Deployment & Doku

> Konzept: §11 (Roll-out), §11.1 (Rollback), §15.3 (Kaskaden-Inventur), §15.7 (Queue-Worker), §15.8 (Permissions)

## Ziel

Sicherstellen, dass die UCS-Integration auf einer produktiven Instanz
risikoarm aktiviert werden kann, mit dokumentiertem Rollback-Pfad und
ohne Bauchgefühl-Entscheidungen während des Go-Lives.

## Scope / Anforderungen

- Supervisor-/Systemd-Snippet für den Queue-Worker mit korrektem
  Timeout.
- `deploy.sh`-Anpassung für die neuen Migrationen + Settings-Migration
  + Permission-Seeder.
- README-Sektion „UCS@school-Integration“.
- Schriftlicher Roll-out-Plan mit Verantwortlichkeiten + Rollback-
  Anleitung.
- Inventur aller FK auf `groups.id`.

## Abhängigkeiten

- Alle Implementierungs-Pakete (01–08) abgeschlossen und 09 grün.

## Aufgaben

### Inventur (Vorarbeit)

- [ ] **Kaskaden-Inventur `groups.id`** (§15.3):
  - In `database/migrations/` nach `->references('id')->on('groups')`
    grepen, alle Treffer in
    `docs/ucs-cascade-inventory.md` dokumentieren.
  - Für jede Referenz festlegen, ob `ucs:purge-stale-classes` sie in
    der Kaskade aufräumt (siehe Paket 05).
  - PR an Paket 05 zurück, falls Anpassungen nötig sind.

### Deployment-Skripte

- [ ] **`deploy.sh`** ergänzen (im richtigen Schritt nach
  `composer install`):
  - `php artisan migrate --force` (deckt sowohl normale als auch
    Settings-Migrationen ab).
  - `php artisan db:seed --class=PermissionSeeder --force`
    (falls Permissions in einem Seeder gepflegt werden).
  - `php artisan queue:restart` (damit neuer `SyncUcsSchoolJob`-Timeout
    greift).
- [ ] **Supervisor-Snippet** unter `docs/supervisor/ucs-worker.conf`
  ablegen:
  ```ini
  [program:elterninfo-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=php /var/www/elterninfo/artisan queue:work --queue=default --timeout=900 --memory=512 --tries=1
  autostart=true
  autorestart=true
  numprocs=1
  redirect_stderr=true
  stdout_logfile=/var/log/elterninfo/worker.log
  stopwaitsecs=920
  ```

### Dokumentation

- [ ] **`readme.md`** um eine Sektion „UCS@school-Integration“ ergänzen:
  - Voraussetzungen (Kelvin-Service-Account, OIDC-Client).
  - Setup-Schritte (Tab konfigurieren, Test-Button, Sync-Button).
  - Hinweis auf das vollständige Konzept und die TODO-Pakete.
- [ ] **`docs/ucs-rollout-runbook.md`** (neu) mit:
  - Schritt-für-Schritt-Anleitung gemäß §11.
  - Pilotphase: Welche Eltern-Gruppe wird zuerst freigeschaltet?
  - Eskalationspfad (wer wird im Fehlerfall benachrichtigt?).
  - Vollständiger Rollback-Plan aus §11.1.
- [ ] **`docs/ucs-cascade-inventory.md`** (neu) – siehe Inventur oben.

### Roll-out-Plan

- [ ] T-7 d: Service-Account in UCS anlegen, Credentials ans Ops-Team.
- [ ] T-3 d: Auf Staging:
  - Migrationen laufen lassen.
  - Settings im UI füllen.
  - `ucs:ping` (Konsole) und Test-Button (UI).
  - `sync:ucs-parents --dry-run` ausführen und Counter reviewen.
  - Linking-Kandidaten ausreviewt.
- [ ] T-1 d: DB-Snapshot auf Produktion.
- [ ] T-0 (außerhalb Hauptzeit):
  1. Maintenance-Modus an (optional).
  2. Migration deployen.
  3. UI-Settings füllen.
  4. `ucs:ping`.
  5. Manueller Sync via UI-Button.
  6. Sync-Log und `last_sync_status` prüfen.
  7. Maintenance-Modus aus.
  8. OIDC-Login für Pilot-Gruppe per `maildomain` im Keycloak-Tab
     freischalten.
- [ ] T+1 d: Klassisches Passwort-Login bleibt verfügbar, bis Pilot
  bestätigt funktioniert.
- [ ] T+7 d: Vollständiger Roll-out aller Eltern.

### Smoke-Tests Produktion

- [ ] Nach erstem Echt-Sync manuell prüfen:
  - Anzahl `users` mit `ucs_source='kelvin'` ≈ Erwartung?
  - Anzahl `children` ≈ Erwartung?
  - Anzahl Gruppen mit `ucs_class_url IS NOT NULL` ≈ Schule?
  - Stichprobe: 5 Eltern öffnen, sehen sie die richtigen Klassen?
  - Manuelle Gruppe (`is_auto_provisioned=false`) ist bei diesen
    Eltern noch zugeordnet?

### Monitoring

- [ ] Sentry-Alert auf `SyncUcsSchoolJob`-Failures einrichten
  (`failed()`-Hook ist in Paket 05 vorgesehen).
- [ ] Dashboard-Widget für `UcsSetting::$last_sync_status` (optional,
  ggf. eigenes Folge-Ticket).

## Gelingenskriterien

1. Auf Staging läuft der vollständige Roll-out-Plan in
   `docs/ucs-rollout-runbook.md` durch, ohne dass Abweichungen vom
   Plan notwendig sind.
2. `php artisan schedule:list` zeigt nach Deployment beide UCS-Tasks.
3. Supervisor zeigt den Worker mit `--timeout=900` korrekt laufend.
4. Permission `manage ucs sync` existiert in der Produktiv-DB.
5. Rollback-Probe (Staging): nach simulierter Fehl-Sync werden die
   `users`/`children`/`groups`/`group_user`/`child_user`-Tabellen
   aus dem Snapshot zurückgespielt; Anwendung funktioniert weiter.
6. README enthält die Setup-Anleitung; ein neuer Entwickler kann ohne
   Rückfrage einen lokalen Sync gegen einen Mock starten.

## Out of Scope

- Multi-School-Setup.
- SCIM/Webhooks (Phase 2/3, §13).
- Performance-Tuning des Workers (Memory-Limits, OPcache, …) –
  separate Operations-Aufgabe.

## Aufwand

S – ca. 0,5–1 Personentag (ohne tatsächliche Roll-out-Durchführung).

