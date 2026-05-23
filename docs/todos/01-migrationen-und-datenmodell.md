# TODO-01: Migrationen & Datenmodell-Erweiterungen

> Konzept: §4 (Datenmodell), §8 (Migrationen), §4.2 (SoftDeletes-Trait), §4.3 (Abwärtskompatibilität)

## Ziel

Das DB-Schema **rein additiv** so erweitern, dass die UCS-Provisionierung
darauf aufbauen kann, **ohne** bestehende Funktionen (Care, Schickzeiten,
Krankmeldungen, CheckIn, Mandate, AGs, MediaLibrary, Posts, Reminder,
Pflichtstunden, Stundenplan, Messenger …) zu brechen.

## Scope / Anforderungen

- Nur ADD-only-Migrationen. Keine `drop`/`rename`/destruktiven `change()`
  außer der bewussten `email`-Constraint-Lockerung in §4.1.
- Composite-UNIQUEs wie im Konzept §4.1 verbindlich entschieden.
- SoftDeletes-Trait an `Child` und `Group` ergänzen (Migration alleine reicht
  nicht; ohne Trait wirkt `deleted_at` nicht).
- Neue Hilfstabelle `ucs_link_candidates` für den Initial-Linking-Workflow
  (§5.2, §15.1) anlegen.

## Abhängigkeiten

- Keine Vorgänger-Pakete.
- Vor Migration auf einer realen Instanz: aktuellen Index-Namen für
  `users.email`-UNIQUE verifizieren (Standard ist `users_email_unique`).

## Aufgaben

- [ ] **Migration 1 – `users`** (`2026_05_22_000001_add_ucs_columns_to_users_table.php`):
  - Spalten `ucs_uuid`, `ucs_username`, `ucs_school`, `ucs_synced_at`, `ucs_source`.
  - `ucs_uuid` und `ucs_username` als `UNIQUE` (single).
- [ ] **Migration 1a – `users.email`-Constraint-Swap**
  (`2026_05_22_000001a_swap_email_unique_on_users_table.php`):
  - Drop des bestehenden Single-UNIQUE auf `email` (Index-Name vorher
    via `SHOW INDEX FROM users` bestätigen).
  - Composite-UNIQUE `(email, ucs_source)` als `users_email_source_unique`.
- [ ] **Migration 2 – `children`** (`2026_05_22_000002_…`):
  - Spalten `ucs_username`, `ucs_uuid` (UNIQUE), `ucs_school`,
    `ucs_synced_at`, `ucs_source`.
  - Composite-UNIQUE `(ucs_school, ucs_username)` als
    `children_school_username_unique`.
  - `softDeletes()` **nur wenn** `deleted_at` noch nicht existiert.
- [ ] **Migration 3 – `child_user`** (`2026_05_22_000003_…`):
  - Spalten `is_auto_provisioned` (default `false`), `relation` (nullable, 40),
    `synced_at` (timestamp, nullable).
- [ ] **Migration 4 – `groups` + `group_user`** (`2026_05_22_000004_…`):
  - `groups`: `ucs_class_url` (UNIQUE), `ucs_source` (default `'local'`),
    `ucs_synced_at`, `softDeletes()` (falls noch nicht vorhanden).
  - `group_user`: `is_auto_provisioned` (default `false`),
    `provisioned_via_child_id` (FK auf `children`, `nullOnDelete`),
    `synced_at`.
- [ ] **Migration 5 – `ucs_link_candidates`** (`2026_05_22_000005_…`):
  - Spalten + FK + UNIQUE wie in §8.
- [ ] **Model-Anpassungen:**
  - `app/Model/Child.php`: `use SoftDeletes;`.
  - `app/Model/Group.php`: `use SoftDeletes;`.
  - `app/Model/User.php`: bestehende `children()`/`groups()` um
    `->withPivot([...])` erweitern (siehe §4.2).
  - Scopes auf `Child`: `scopeFromUcs()`, `scopeLocal()` ergänzen.
  - Sinngemäß auf `Group`: `scopeFromUcs()`, `scopeLocal()`.
- [ ] **Rollback prüfen:** jede `down()`-Methode entfernt **ausschließlich**
  die jeweils hinzugefügten Spalten/Indizes (Migration 1a stellt das
  alte Single-UNIQUE wieder her).
- [ ] **Migration in lokaler Testumgebung gegen vollen Bestands-Dump fahren** und alle bestehenden Tests grün halten.

## Gelingenskriterien

1. `php artisan migrate` läuft auf einer Kopie der Produktiv-DB ohne Fehler durch.
2. `php artisan migrate:rollback --step=5` setzt alle Änderungen rückstandsfrei zurück (Spalten/Indizes wieder identisch zum Vorzustand).
3. `php artisan test` läuft komplett grün (keine Regression im Bestand).
4. `Child::find($id)->delete()` setzt `deleted_at`, `Child::find($id)` liefert danach `null`, `Child::withTrashed()->find($id)` liefert das Objekt.
5. `User` mit zwei Accounts (`local` + `kelvin`) und identischer E-Mail kann gespeichert werden; zwei Accounts mit `local` + identischer E-Mail werfen DB-Constraint-Verletzung.
6. Die Tabelle `ucs_link_candidates` lehnt das Duplikat `(child_id, ucs_username)` auf DB-Ebene ab.

## Out of Scope

- Befüllen der neuen Spalten – das übernimmt Paket 04 (`UcsSyncService`).
- Seeder/Fixtures – Paket 09.
- Permissions – Paket 02.

## Aufwand

M – ca. 1–2 Personentage inkl. Test-Dump und Rollback-Verifikation.

