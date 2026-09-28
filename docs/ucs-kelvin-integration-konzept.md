# UCS@school / Kelvin-Integration

Stand: 28.09.2026 · optional, standardmäßig aus (`UCS_ENABLED=false`)

Die Integration übernimmt Eltern, Kinder und Klassen aus UCS@school (Kelvin REST API)
und erlaubt den Login über den Schul-IdP (Keycloak/OIDC). Ohne Aktivierung ändert sich
am Verhalten nichts. Die Arbeitspakete stehen in `docs/todos/01–12`; der Code verweist
mit `§` auf die Abschnitte dieses Dokuments.

---

## 1. Ziel

* Eltern und Kinder nicht mehr per Excel pflegen, sondern aus UCS übernehmen.
* Klassen als Gruppen spiegeln; Eltern erhalten die Gruppen ihrer Kinder automatisch.
* Anmeldung mit dem Schul-Login; bestehende lokale Konten bleiben nutzbar.
* Nichts Lokales wird überschrieben oder gelöscht, was nicht aus UCS stammt.

## 2. Überblick

| Baustein | Aufgabe |
|---|---|
| `KelvinClient` | HTTP-Zugriff, Token, Pagination, Fehlerklassen |
| `KelvinNormalizer`, DTOs | tolerante Normalisierung unterschiedlicher Kelvin-Versionen |
| `UcsSyncService` | Bulk-Sync und Einzel-Sync (JIT), Upsert-Regeln, Telemetrie |
| `LinkCandidateService` | Zusammenführen lokaler Kinder mit UCS-Kindern |
| `UcsLoginController` | OIDC-Login, Kontoverknüpfung, Single-Logout |
| `UcsSetting` | Konfiguration im Admin-Tab „UCS@school“ |

## 3. Zugriff auf die Kelvin-API

Basis-URL, Dienstkonto und Schule stehen in `UcsSetting` (Zugangsdaten verschlüsselt).
`php artisan ucs:ping [--debug]` prüft die Verbindung (`GET /schools/{school}`, das auch
hinter einem Proxy mit Whitelist freigegeben ist).

## 4. Datenmodell

### 4.1 Migrationen (nur additiv, rückbaubar)

* `users`: `ucs_uuid` (Kelvin `record_uid`), `ucs_username` (eindeutig), `ucs_school`,
  `ucs_synced_at`, `ucs_source` (`local` | `kelvin`), `ucs_oidc_sub`.
* `users.email`: eindeutig je `(email, ucs_source)` statt global – ein UCS-Konto kann
  neben einem lokalen Konto gleicher Adresse existieren, bis es verknüpft ist.
* `children`: UCS-Spalten, `UNIQUE(ucs_school, ucs_username)`, Soft-Delete.
* `child_user`: `is_auto_provisioned`, `relation`, `synced_at`.
* `groups`: `ucs_class_url` (eindeutig), `ucs_source`, `ucs_synced_at`, Soft-Delete;
  `group_user`: `is_auto_provisioned`, `provisioned_via_child_id`, `synced_at`.
* `ucs_link_candidates`: Kandidaten für das Zusammenführen (§8).

### 4.2 Modelle

`Child` und `Group` erhalten Soft-Delete und die Scopes `fromUcs()` / `local()`.

## 5. Synchronisation

### 5.1 Bulk-Sync

`php artisan sync:ucs-parents [--dry-run]` läuft synchron (kein Queue-Worker nötig) und
wird vom Scheduler nach `UcsSetting::sync_cron` gestartet, sofern `enabled` und
`sync_enabled` gesetzt sind. `SyncUcsSchoolJob` bleibt für manuelles asynchrones Auslösen.

### 5.2 Upsert-Regeln

* Eltern: nur Konten mit Rolle `legal_guardian` an der konfigurierten Schule; Zuordnung
  über `ucs_username`, dann `record_uid`, dann – nur für noch nicht verknüpfte lokale
  Konten – über die E-Mail-Adresse. Gelöschte lokale Konten werden nicht wiederbelebt.
* Kinder aus `legal_wards` bzw. rückwärts aus `students.legal_guardians`.
* Lokale Kinder (`ucs_source = local`), die per Name + Klasse passen, werden nicht
  dupliziert, sondern als Kandidat vorgemerkt (§8).
* Nur Datensätze mit `ucs_source = kelvin` bzw. `is_auto_provisioned = true` werden
  geändert oder entfernt – manuelle Verknüpfungen nie.

### 5.3 Deaktivierung und verwaiste Eltern

Eltern, die in UCS nicht mehr vorkommen, verlieren ihre UCS-Beziehungen und abgeleiteten
Gruppen; manuelle Beziehungen bleiben.

### 5.4 Sperre und Telemetrie

Exklusiver Cache-Lock gegen parallele Läufe; `last_sync_at/status/message` und Zähler in
`UcsSetting`, sichtbar im Admin-Tab.

## 6. Login über den Schul-IdP (OIDC)

### 6.1 Konfiguration

Socialite-Treiber `ucs` (Alias auf Keycloak). Die Zugangsdaten kommen aus
`KeycloakSetting` (Admin-Tab „OIDC / Keycloak“) und werden beim Boot in `config()`
gespiegelt.

### 6.2 Zuordnung

1. `users.ucs_oidc_sub` = OIDC-`sub`
2. `users.ucs_username` = `preferred_username` (danach `sub` nachtragen)
3. Just-in-time-Sync des Elternteils (`on_login_fallback`)
4. sonst Seite „Konto wird vorbereitet“

Nie über die E-Mail-Adresse zuordnen.

### 6.3 Kontoverknüpfung

Eingeloggte lokale Nutzer können ihr Konto mit dem Schul-Login verknüpfen
(Menü „Mit Schul-Login verknüpfen“). Konten ohne E-Mail-Adresse werden zur Eingabe
aufgefordert (`EnsureUserHasEmail`, nur für `ucs_source = kelvin`).

### 6.4 Aktualisierung beim Login

Nach Passwort-/Magic-Link-Login eines UCS-Kontos läuft `SyncSingleUcsParentJob` nach der
Antwort (nicht blockierend, kurzer Timeout).

## 7. Klassen und Gruppen

### 7.3 Klassenwechsel und Aufräumen

Klassen, die in UCS verschwinden, werden weich gelöscht. `ucs:purge-stale-classes`
entfernt sie endgültig nach `purge_after_days` (Standard 14) einschließlich Beiträgen, die
nur dort hingen, Chat, und Pivot-Einträgen – in einer Transaktion je Gruppe.
Kombinierte Klassen werden vollständig über `child_group` abgebildet.

### 7.6 Diagnose

`ucs:ping --debug` zeigt Konfiguration, DNS/TCP und Latenz.

## 8. Zusammenführen lokaler Kinder (Initial-Linking)

Kandidaten erscheinen im Admin-Tab und werden bestätigt oder verworfen
(`LinkCandidateService`; CLI: `php artisan ucs:link-child`). Bestätigen setzt
`ucs_username`/`ucs_uuid` am lokalen Kind; idempotent.

## 9. Anbindung an das Familienmodell

Kind-Beziehungen über `GuardianshipService::syncFromSource` (`source = ucs`), Gruppen
über `GroupMembershipService`, neue Personen werden über `FamilyBuilder` Familien
(`source = ucs`); gesperrte Familien bleiben unverändert.

## 10. Einstellungen

Admin › Einstellungen › UCS@school: Aktivierung, Verbindung, Seitengröße, Timeouts,
Sync-Zeitplan, JIT-Login, Aufräumfrist, Verbindungstest, Sync jetzt starten
(Recht `manage ucs sync`), Link-Kandidaten.

## 11. Aktivierung und Rollback

1. Deploy (Migrationen additiv), Permission-Seeder `UcsSyncPermissionSeeder`.
2. Zugangsdaten eintragen, `ucs:ping`.
3. `sync:ucs-parents --dry-run`, Kandidaten prüfen.
4. `enabled` und `sync_enabled` aktivieren.
5. Rollback: `enabled` aus – es laufen keine Syncs mehr; importierte Daten bleiben.

## 14. Konfiguration

### 14.3 Spiegelung in `config()`

`UcsServiceProvider` überträgt `UcsSetting`/`KeycloakSetting` beim Boot in
`config('services.ucs')`, damit Socialite und Bibliotheken sie finden.

### 14.9 Quelle der OIDC-Zugangsdaten

Maßgeblich ist `KeycloakSetting` (Datenbank), nicht `.env`.

## 15. Randfälle

### 15.1 Namensgleichheit

Treffer über Name + Klasse führen nie automatisch zusammen, sondern erzeugen nur einen
Kandidaten. Gibt es mehrere gleichnamige lokale Kinder in der Klasse, wird das erste
vorgeschlagen – die Verwaltung prüft vor dem Bestätigen.
