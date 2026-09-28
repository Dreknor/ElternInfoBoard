# Kontoverknüpfungen: Konzeptprüfung, Umsetzungsstand und Rollout

Stand: 28.09.2026 · Branch `integration/ucs-familienmodell` (Basis `dev`)

Geprüft wurde die „Anforderungsspezifikation ElternInfoBoard – Abfragen und Mandate“
(im Folgenden *Spezifikation*) gegen den Bestand in `dev` und gegen die bereits
vorhandene Umsetzung des kind-zentrierten Familienmodells (`feat/familienmodell`,
FAM-00 bis FAM-16).

---

## 1. Ergebnis in Kürze

| Thema | Bewertung |
|---|---|
| Ziel (Rechte je Kind, getrennte Eltern, Patchwork) | richtig, wird mit dem Familienmodell erreicht |
| Tabelle `child_mandates` | **nicht verwendbar** – Name ist bereits belegt (Abholvollmachten) |
| „Account Splitting“ | **entfällt** – Eltern haben bereits eigene Konten; das Problem ist die `sorg2`-Kopplung |
| `family_unit_id` je Verknüpfung | ersetzt durch `families` + `users.family_id` |
| Migrationsbefehl `migrate:refactor-parent-mandates` | ersetzt durch `family:migrate-from-sorg2` (kein `migrate:*`-Namensraum) |
| Rückmeldungs-Modi | umgesetzt als `scope` = `child` / `person` / `family` |
| Digitale Formulare (Arbeitspakete 3–5) | **eigenes Projekt**, nicht Teil dieser Umstellung |
| Rollback, Feature-Schalter, API/App, UCS, abgerechnete Zeiträume | fehlten in der Spezifikation, sind ergänzt |

Die Umstellung ist in `dev` integrierbar, ohne das Verhalten des Bestandssystems zu
ändern: Solange `FAMILY_RESOLVER=legacy` gilt (Standard), bleibt alles wie bisher.
Das wurde an einer Kopie des Produktivbestands nachgewiesen (Abschnitt 6).

---

## 2. Prüfung der Spezifikation im Einzelnen

### 2.1 Datenmodell (Spez. §3.1)

Die Spezifikation fordert eine Pivot-Tabelle `child_mandates`. In `dev` existiert
`child_mandates` bereits – dort liegen **Abholvollmachten** (Person, die ein Kind
abholen darf; `ChildMandate`, E-Mail-Benachrichtigung, App-Endpunkt
`storeMandate`). Eine Umwidmung würde Bestandsdaten und App brechen.

Umgesetzt wird stattdessen die bestehende Eltern-Kind-Tabelle `child_user` als
qualifizierte Beziehung (Model `ChildGuardian`):

| Spezifikation | Umsetzung (`child_user`) | Anmerkung |
|---|---|---|
| `is_legal_guardian` | `has_custody` | steuert Rückmeldungen pro Kind (nur Sorgeberechtigte) |
| `can_view_records` | `can_manage` + Policy `viewHealth` | Krankmeldungen = Gesundheitsdaten (Art. 9 DSGVO) |
| `can_submit_forms` | derzeit = `has_custody` | eigenes Flag erst mit dem Formular-Modul sinnvoll |
| `receives_notifications` | `receives_information` | steuert abgeleitete Gruppenmitgliedschaften |
| – | `relation` (Mutter, Vater, Pflege, Partner, Großeltern …) | Standardrechte je Beziehungsart |
| – | `source` (manual, import, ucs, migration), `reviewed_at` | Herkunft und Prüfstatus |
| – | `valid_until` | befristete Beziehungen |
| `family_unit_id` | `families` + `users.family_id` | siehe 2.2 |

### 2.2 Familie / Abrechnungseinheit

Die Spezifikation hängt `family_unit_id` an jede Verknüpfung. Das ist
widersprüchlich: Dieselbe Person bekäme je Kind eine andere Einheit, und
Pflichtstunden, Reinigung oder Termine ließen sich keiner Einheit eindeutig
zuordnen. Umgesetzt ist eine Familie als eigene Einheit (`families`), zu der eine
Person gehört (`users.family_id`). Kinder hängen nicht an der Familie, sondern an
den Personen; die Familie wird aus den Beziehungen abgeleitet (`FamilyBuilder`)
oder manuell gepflegt (gesperrte Familien fasst die Automatik nicht an).

Grenze: Eine Person gehört zu genau einer Familie. Für Patchwork reicht das, weil
Rechte am Kind über `child_user` laufen, nicht über die Familie.

### 2.3 Legacy-Migration (Spez. §3.2)

* **Account Splitting entfällt.** Im Bestand hat jeder Elternteil ein eigenes Konto;
  „Familie“ ist die 1:1-Kopplung `users.sorg2`, über die der Partner die Kinder,
  Pflichtstunden, Rückmeldungen und Krankmeldungen des anderen „erbt“. Es gibt
  keine Konten mit zwei E-Mail-Adressen, also auch keine Passwort-Einladungen.
* **Mandate-Befüllung** erfolgt durch `family:migrate-from-sorg2`: Paare werden zu
  Familien; Kinder, die jemand bisher nur über den Partner sah, werden direkt
  verknüpft (`source = migration`, zur Prüfung markiert). `sorg2` bleibt
  unverändert (Rollback).
* **Historische Zuordnung** ist nicht nötig: Pflichtstunden, Buchungen und
  Rückmeldungen tragen bereits die ID der handelnden Person. Das eigentliche
  Risiko ist ein anderes (siehe 2.4 Pflichtstunden).
* Der Befehlsname `migrate:refactor-parent-mandates` würde im Namensraum der
  Laravel-Migrationsbefehle liegen (Verwechslungsgefahr mit `migrate:fresh`).

### 2.4 Module (Spez. §2, Arbeitspaket 2)

| Modul | Spezifikation | Stand |
|---|---|---|
| Pflichtstunden | Soll je Kind oder Familie; Handelnder = `user_id` | umgesetzt: Basis Familie/Kind, geteilte Kinder getrennt/anteilig/zusammen, Obergrenze, Gruppenfilter. Mit der Kontoführung aus `dev` (ermäßigt/individuell, Übertrag) zusammengeführt. **Zusätzlich:** abgelaufene Zeiträume werden beim Ansehen nicht mehr neu berechnet, sonst hätte die Umstellung abgerechnete Salden verändert. |
| Listen / Termine | Eintrag mit `user_id` + `child_id`, „1 Helfer je Kind“ | Terminlisten: Buchung je Kind (`listen_termine.child_id`) umgesetzt. **Offen:** Eintragungslisten (`listen_eintragungen`) je Kind. |
| Rückmeldungen | Modus A je Kind (ein Sorgeberechtigter reicht), Modus B je Elternteil | umgesetzt als `scope = child` bzw. `person`; `family` = bisheriges Verhalten. **Offen:** Lesebestätigungen gelten weiter je Familie, nicht je Kind/Person. |
| Krankmeldungen | an `child_id` gebunden, Sicht für alle Berechtigten, optional Info an anderen Elternteil | Bindung und Sicht umgesetzt (Policy `reportSick`, `viewHealth`). **Offen:** Benachrichtigung des anderen Elternteils. |
| Import | zwei Konten, Mandate, Abgleich per E-Mail | umgesetzt: Schüler-Import (eine Zeile je Kind, Schüler-ID, bis zu drei Bezugspersonen, Vorschau vor dem Speichern, Abgänger); Eltern-/Aufnahme-Import bilden Familien statt `sorg2`. |

### 2.5 Recht, Audit, Formulare (Spez. §4–5, Arbeitspakete 3–5)

Beziehungen und Familien sind auditiert (`owen-it/laravel-auditing`). Das
unveränderliche Formular-Audit (Hash, IP, Versionierung), Template-Builder,
Kampagnen, Export an `schulsoftware.schule` und eine eigene Formular-API sind ein
eigenes Vorhaben (Ansatz im Branch `claude/schulzentrum-digital-forms-5rqtvv`). Es
setzt auf den Kind-Beziehungen auf und sollte **nach** dem Umschalten auf das
Familienmodell begonnen werden.

Hinweise zur Spezifikation für dieses spätere Projekt:
* IP-Adressen nur mit Zweck und Löschfrist speichern; „anonymisiert *oder*
  gespeichert“ ist keine Entscheidung.
* „Unveränderlich“ braucht eine technische Garantie (append-only, keine
  Update-/Delete-Rechte), nicht nur eine Konvention.
* `can_submit_forms` als eigenes Flag erst dann einführen.

### 2.6 Was der Spezifikation fehlte

1. **Rollback und schrittweises Umschalten.** `FAMILY_RESOLVER=legacy|child_centric`
   bleibt ohne Deploy umschaltbar; `sorg2` wird für Zweier-Familien mitgepflegt
   (`FAMILY_DUAL_WRITE_SORG2`).
2. **UCS@school als Quelle.** Beziehungen aus UCS kommen mit `source = ucs`; manuelle
   Beziehungen bleiben beim Sync unberührt. UCS ist optional (`UCS_ENABLED=false`).
3. **API und App** (Abschnitt 4).
4. **Gelöschte Konten** in `sorg2`-Kopplungen (im Bestand 11 Fälle) und Dreiecke
   (A→B, B→C) werden gemeldet, nicht geraten.
5. **Abgerechnete Pflichtstunden-Zeiträume** dürfen durch die Umstellung nicht
   neu berechnet werden.

---

## 3. Umsetzungsstand im Integrationsbranch

Commits über `dev`:

1. Merge UCS@school/Kelvin (`feat/ucs-01-migrationen-datenmodell`) – doppelte
   Settings-Klasse `KeyCloakSetting`/`KeycloakSetting` vereinheitlicht.
2. Merge Familienmodell (`feat/familienmodell`) – Konflikte mit neuerem `dev`-Code
   aufgelöst (Pflichtstunden-Konten, Reinigung, Abfragen, Import, Views);
   verbliebene direkte `sorg2`-Zugriffe (Betreuungskontakte, iCal, Erinnerungen)
   umgestellt.
3. Bestandsschutz Pflichtstunden, präziserer `family:status`.
4. Merge App-API v1 (`feat/app-api-v1`) mit Umstellung B-80.
5. NativePHP entfernt.

Tests: 1079 Tests; gegenüber `dev` (80 Fehlschläge) bleiben 36, alle bereits in
`dev` vorhanden oder instabil (u. a. `TerminlisteRueckmeldungTest`, Settings-Gruppe
`Care` unter SQLite). Neu: `PflichtstundenKontoMigrationTest`,
`API\V1\FamilyModelTest`.

---

## 4. API und Eltern-App

Die App (`elterninfo_app`) nutzt die API v1 und teils die alte API. Alle Änderungen
sind **additiv**; im Modus `legacy` ändert sich für die App nichts.

| Bereich | Änderung | Folge für die App |
|---|---|---|
| `children[].my_rights` | wird berechnet (`manage`, `view_health`, zusätzlich `custody`, `information`) | App blendet Krankmeldung/Schickzeiten bereits danach aus – funktioniert ohne Update |
| Krankmeldung, Kind-Einstellungen, AG-Anmeldung | verlangen Verwaltungsrecht am Kind | Großeltern ohne Recht erhalten 403 (Buttons sind in der App bereits ausgeblendet) |
| `feedback.scope`, `feedback.targets[]` | neu (je Kind: `child_id`, `child_name`, `can_answer`, `responded`, `answered_by`) | **App-Update nötig**, um je Kind zu antworten |
| `POST …/feedback`, `…/abfrage` | optional `child_id` | ohne `child_id`: bei genau einem beantwortbaren Kind wird dieses verwendet, bei mehreren **422** |
| `feedback.responded`, `todo` | bei Rückmeldung pro Kind: erst erledigt, wenn alle eigenen Kinder beantwortet sind | Aufgabenliste bleibt korrekt |
| Familie, Buchungen, Pflichtstunden | über den Resolver statt `sorg2` | keine |

**Empfehlung:** Die App-Version mit Antwortzielen (`targets`/`child_id`) vor dem
Umschalten auf `child_centric` veröffentlichen und über `APP_MIN_VERSION`
erzwingen. Bis dahin beantworten Eltern mit mehreren betroffenen Kindern
Rückmeldungen pro Kind im Web.

---

## 5. Rollout im Bestandssystem

1. Datenbank sichern.
2. Deploy (`FAMILY_RESOLVER=legacy` bleibt gesetzt), `php artisan migrate`.
   Alle Migrationen sind additiv; bestehende Verknüpfungen erhalten alle Rechte.
3. `php artisan family:migrate-from-sorg2 --dry-run --report=sorg2.csv` und den
   Report prüfen (Konflikte, übernommene Beziehungen).
4. `php artisan family:migrate-from-sorg2` und
   `php artisan family:rebuild --only-unassigned`.
5. Konflikte in der Familienverwaltung klären (Dreiecke; Verweise auf gelöschte
   Konten sind nur Hinweise).
6. `php artisan pflichtstunden:jahresabschluss` für den zuletzt abgelaufenen
   Zeitraum, damit Überträge festgeschrieben sind.
7. `php artisan family:status` – ohne Blocker bereit.
8. App-Update mit Antwortzielen ausrollen (Abschnitt 4).
9. `FAMILY_RESOLVER=child_centric` setzen, `php artisan config:clear`.
10. Übernommene Beziehungen und Meldungen „Verbindung ist falsch“ abarbeiten.

**Rollback:** `FAMILY_RESOLVER=legacy`. `sorg2` ist unverändert bzw. wird
mitgepflegt; Pflichtstunden-Regeln und -Konten werden in beiden Modi gefunden.

---

## 6. Nachweis an einer Kopie des Produktivbestands

Lokale Datenbank (420 aktive Konten, 334 Kinder, 549 Kind-Verknüpfungen,
94 `sorg2`-Kopplungen), vorher gesichert:

* Migrationen: fehlerfrei, keine doppelten Verknüpfungen.
* `family:migrate-from-sorg2`: 33 Paare, 194 Einzelpersonen, 3 einseitige
  Kopplungen repariert, 7 Kind-Beziehungen zur Prüfung übernommen, 12 Konflikte
  (11 × Partnerkonto gelöscht, 1 Dreieck).
* **Modus `legacy` nach der Migration:** 0 Abweichungen bei sichtbaren Kindern,
  Familien und Pflichtstunden gegenüber vorher.
* **Modus `child_centric`:** genau eine Person verliert die Sicht auf Kinder – der
  ungeklärte Dreiecksfall. Alle anderen sehen dieselben Kinder wie bisher.

---

## 7. Offene Punkte

| Priorität | Punkt |
|---|---|
| hoch | App: Antwortziele je Kind (`feedback.targets`, `child_id`) |
| mittel | Eintragungslisten „1 Eintrag je Kind“ |
| mittel | Lesebestätigung wahlweise je Kind oder je Person |
| mittel | Krankmeldung: optionale Info an weitere Berechtigte |
| niedrig | Konzeptdokumente `docs/kind-zentriertes-familienmodell-konzept.md` und `docs/ucs-kelvin-integration-konzept.md` fehlen bzw. sind leer, werden aber im Code referenziert |
| später | Digitale Formulare (eigenes Projekt) |
