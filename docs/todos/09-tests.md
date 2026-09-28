# TODO-09: Tests, Fixtures & Last-Test

> Konzept: §10 (Teststrategie)

## Ziel

Die in §10 spezifizierten Tests vollständig umsetzen, mit besonderem
Fokus auf die **Regressions-Tests**, die das Konzept als kritisch
markiert (manuelle Pivots, Klassen-Wechsel, lokale Kinder, `detach`-
Korrektheit). Plus Performance-Smoke-Test für den Bulk-Sync.

## Scope / Anforderungen

- JSON-Fixtures unter `tests/Fixtures/kelvin/`.
- Unit-Tests pro Service-Klasse (`KelvinClient`, `UcsSyncService`,
  `LinkCandidateService`).
- Feature-Tests für Artisan-Commands und OIDC-Callback.
- Regression-Tests genau wie in Konzept-Tabelle §10 aufgelistet.
- Last-Test als gesonderter `@group performance`-Test, der in CI
  optional ausgeführt wird.

## Abhängigkeiten

- 03, 04, 05, 06, 07, 08 – alle vorherigen Pakete sollten mindestens
  als Skelett vorliegen, damit Tests grün gefahren werden können.

## Aufgaben

### Fixtures

- [ ] `tests/Fixtures/kelvin/legal_guardian.list.json` (3 Eltern, paginiert).
- [ ] `tests/Fixtures/kelvin/student.list.json` (5 Schüler, davon 1 Kombiklasse,
      1 ohne `legal_wards`, 1 ohne `email`).
- [ ] `tests/Fixtures/kelvin/user.findUser.found.json` (1 Eltern für JIT).
- [ ] `tests/Fixtures/kelvin/auth.json` (Token-Response).
- [ ] `tests/Fixtures/kelvin/schools.json` (Single-School-Response).

### Unit-Tests

- [ ] `tests/Unit/Services/Ucs/KelvinClientTest.php`
  - Token-Cache (kein zweites `/auth/`).
  - Token-TTL-Ablauf → Re-Auth.
  - 401 → einmaliger Re-Auth, dann Exception.
  - 5xx → 3 Retries, dann `KelvinUnavailableException`.
  - Pagination über `Generator`.
  - Token im Cache ist verschlüsselt.
- [ ] `tests/Unit/Services/Ucs/UcsSyncServiceTest.php`
  - Idempotenz: 2× `run()` → identischer DB-Zustand.
  - Auto-vs-Manuell-Diskriminator.
  - Detach-Korrektheit (siehe Regressions).
  - `syncSingleParent`: kein Detach bei Login.
  - JIT-Timeout → `null` return + Negativ-Cache-Eintrag.
  - Per-Eltern-Fehler-Isolation.
  - Telemetrie-Lock + TTL.
- [ ] `tests/Unit/Services/Ucs/LinkCandidateServiceTest.php`
  - `confirm()` setzt `ucs_username` + `confirmed_at`.
  - Idempotent bei zweitem `confirm()`.
  - `reject()` markiert `payload.status='rejected'`.

### Feature-Tests

- [ ] `tests/Feature/Console/SyncUcsParentsCommandTest.php`
  - `--dry-run` → keine Mutation, Counter-Output.
  - Mit `enabled=false` → Exit 2.
  - Erfolgreicher Lauf → `last_sync_status='success'`.
- [ ] `tests/Feature/Console/UcsPurgeStaleClassesTest.php`
  - Klasse SoftDeleted seit 15 Tagen → wird hart gelöscht (inkl. Kaskade).
  - Lokale Gruppe SoftDeleted seit 15 Tagen → bleibt unangetastet.
- [ ] `tests/Feature/Console/UcsLinkChildCommandTest.php`
  - Setzt `ucs_username`, entfernt Kandidat.
  - Doppelter Aufruf no-op.
- [ ] `tests/Feature/Auth/UcsLoginTest.php`
  - Primary-Match (`ucs_uuid`).
  - Secondary-Match (`ucs_username`) + Backfill.
  - JIT-Match via gemocktem `UcsSyncService`.
  - Pending-Redirect bei Timeout.
  - `is_active=false` → 403.
  - Negativ-Cache verhindert zweiten API-Call.
  - RateLimit greift bei > 30 Requests/min.
- [ ] `tests/Feature/Http/SettingsUcsTabTest.php`
  - Speichern, ohne `kelvin_password` zu überschreiben.
  - Cron-Validierung lehnt Müll ab.
  - Sync-Button dispatcht Job.
  - Permission-Schutz für Sync-Button und Credentials-Felder.

### Regression-Tests (Kernforderungen aus §10)

- [ ] **Manuell vergebene Gruppe bleibt nach Sync erhalten.**
- [ ] **`detach`-Korrektheit:** User mit 3 Auto + 2 Manuell, Soll = 1 Auto
  → resultiert in {1 Auto + 2 Manuell}.
- [ ] **Klassen-Wechsel** entfernt nur Auto-Pivot der alten Klasse.
- [ ] **Lokal angelegtes Kind** wird vom Sync nie verändert oder gelöscht.
- [ ] **Initial-Linking** erzeugt **einen** Kandidaten und **kein** Duplikat.
- [ ] **Kombiklasse** → `class_id` = erste alphabetisch, 2 Auto-Pivots.
- [ ] **Bestehender `Child`-Code** (Care, Schickzeiten, Krankmeldungen,
  CheckIn, Mandate, MediaLibrary, AGs) bleibt grün – die bestehende
  Test-Suite läuft ohne Anpassung.
- [ ] **`child_user`-Pivot ohne neue Spalten** verhält sich wie vorher
  (Default `is_auto_provisioned=false`).
- [ ] **SoftDelete-Sichtbarkeit** für `Child` und `Group`.

### Last-Test

- [ ] `tests/Feature/Performance/UcsBulkSyncPerformanceTest.php` (Group `@group performance`):
  - Generiert In-Memory-Fixtures mit 2 000 Eltern, 4 000 Kindern.
  - Misst die Laufzeit von `UcsSyncService::run()` mit gemocktem `KelvinClient`.
  - Assert: < 60 Sekunden auf einem Standard-CI-Worker.

### CI-Integration

- [ ] `phpunit.xml` ggf. einen Test-Suite-Eintrag `<testsuite name="Performance">`
  hinzufügen, der nur per `--testsuite Performance` läuft (nicht im Default).

## Gelingenskriterien

1. `php artisan test` läuft vollständig grün (alle Pakete grün).
2. `php artisan test --testsuite Performance` läuft den Last-Test
   isoliert und besteht das 60-s-Limit.
3. Coverage-Report (optional, falls Pipeline existiert) zeigt
   `App\Services\Ucs\*` ≥ 85 %.
4. Alle in §10 namentlich genannten Regressions-Tests existieren als
   eigene Test-Methode (nicht nur als Assertion innerhalb anderer Tests).

## Out of Scope

- E2E-Browser-Tests (Dusk) – falls gewünscht, eigenes Ticket.
- Echte Kelvin-Tests gegen Staging – das ist Teil des Roll-out
  (Paket 10), nicht der Unit-Test-Suite.

## Aufwand

L – ca. 2–3 Personentage.

