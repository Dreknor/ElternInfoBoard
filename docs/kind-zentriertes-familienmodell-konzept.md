# Kind-zentriertes Familienmodell

Stand: 28.09.2026 · ersetzt die Kontoverknüpfung `users.sorg2`

Dieses Dokument beschreibt das umgesetzte Modell. Der Code verweist mit `§` auf die
Abschnitte; Entscheidungen sind mit `E1` … `E8` benannt. Die Prüfung der ursprünglichen
Anforderungsspezifikation steht in `docs/konzept-kontoverknuepfung-pruefung.md`.

---

## 1. Ausgangslage

Jeder Elternteil hat ein eigenes Konto. „Familie“ war bisher die 1:1-Kopplung
`users.sorg2`: Der Partner „erbte“ Kinder, Pflichtstunden, Rückmeldungen,
Krankmeldungen, Buchungen und Reinigungsdienste des anderen Kontos. Das bildet
getrennt lebende Eltern, Patchwork, Großeltern oder Pflegeeltern nicht ab:

* Ein Stiefelternteil sieht alle Kinder des Partners, auch fremde.
* Getrennt lebende Eltern mit gemeinsamem Kind bilden entweder eine „Familie“
  (und teilen alles) oder sehen die Aktionen des anderen nicht.
* Rechte (Krankmelden, Gesundheitsdaten, Rückmeldung als Sorgeberechtigter) lassen
  sich nicht je Kind vergeben.

## 2. Ziele und Entscheidungen

Rechte hängen am **Kind**, Abrechnung und Organisation an der **Familie**.

| | Entscheidung |
|---|---|
| E1 | Pflichtstunden-Soll wahlweise je Familie oder je Kind (Setting), Standard = bisher (je Familie). |
| E2 | Rückmeldungen je Kind (Standard für neue), je Familie (bisher) oder je Person. |
| E3 | Beziehungen, die nur über `sorg2` bestanden, werden übernommen, aber als **ungeprüft** markiert. Eltern melden falsche Verbindungen, ändern sie aber nicht selbst. |
| E4 | Der Schüler-Import gleicht Kinder nur über die Schüler-ID der Schulverwaltung ab. |
| E5 | Jede Beziehungsart hat Standardrechte (Mutter/Vater/Sorgeberechtigte/Pflege: alle; Partner: Informationen + Verwalten; Großeltern/Sonstige: nur Informationen). |
| E6 | Beziehungen und Familien pflegt ausschließlich die Verwaltung (Recht `manage families`). |
| E7 | Rückmeldungen pro Kind geben nur Sorgeberechtigte ab – ohne Ausnahme. |
| E8 | Änderungen der Pflichtstunden-Berechnungsgrundlage wirken sofort und werden mit Zeit und Person protokolliert. |

## 3. Begriffe

* **Bezugsperson** – Person mit Beziehung zu einem Kind (`child_user`).
* **Rechte** – `has_custody` (Sorgerecht), `receives_information` (Informationen der
  Kind-Gruppen), `can_manage` (Krankmelden, Schickzeiten, Anwesenheit, Vollmachten, Notizen).
* **Familie** – Abrechnungs- und Organisationseinheit (Pflichtstunden, Reinigung,
  Rückmeldungen je Familie, Buchungen). Eine Person gehört zu höchstens einer Familie.

## 4. Datenmodell

### 4.1 Beziehung Kind ↔ Bezugsperson (`child_user`, Model `ChildGuardian`)

| Spalte | Bedeutung |
|---|---|
| `relation` | `mother`, `father`, `legal_guardian`, `foster_parent`, `partner`, `grandparent`, `other` (E5) |
| `has_custody`, `receives_information`, `can_manage` | Rechte (Bestand: alle `true`) |
| `source` | `manual`, `import`, `ucs`, `migration` |
| `is_auto_provisioned`, `synced_at` | von UCS/Import verwaltet |
| `valid_until` | befristete Beziehung |
| `reviewed_at` | Prüfung einer übernommenen Beziehung (E3) |

`UNIQUE(child_id, user_id)`; Dubletten werden bei der Migration zusammengeführt.
Die Tabelle `child_mandates` bleibt unverändert den **Abholvollmachten** vorbehalten.

### 4.2 Familie (`families`, `users.family_id`)

`name`, `source` (`auto`, `manual`, `import`, `ucs`, `migration`), `is_locked` (die
Automatik fasst gesperrte Familien nie an), `notes`, Soft-Delete. Kinder gehören nicht
zur Familie, sondern zu Personen; die Kinder einer Familie werden abgeleitet.

### 4.3 Kinder

`children.external_id` (Schüler-ID, eindeutig, E4), `status` (`applicant`, `active`,
`left`) mit Ein-/Austrittsdatum; Abgänger werden weich gelöscht.

### 4.4 Zusatzgruppen und Buchungen je Kind

* `child_group` – weitere Gruppen eines Kindes (z. B. kombinierte UCS-Klassen, AGs).
* `listen_termine.child_id`, `listen_eintragungen.child_id` – Buchung gehört zu einem Kind.
* `listen.booking_scope` (`family` | `child`), `posts.read_receipt_scope`
  (`family` | `person` | `child`), `rueckmeldungen.scope` (`family` | `person` | `child`).

## 5. Dienste

### 5.1 FamilyResolver (Umschalter)

Einzige Stelle, die entscheidet, wer zu einer Familie gehört und wer auf welches Kind
zugreift. `FAMILY_RESOLVER=legacy` bildet das bisherige `sorg2`-Verhalten exakt nach,
`child_centric` nutzt Familien und direkte Beziehungen mit Rechten. Der Schalter wird
bei jeder Auflösung gelesen (kein Singleton) – Umschalten und Rollback ohne Deploy.

### 5.2 GuardianshipService

Beziehungen anlegen/ändern/entfernen, Standardrechte, Prüfvermerk, Abgleich aus einer
Quelle (`syncFromSource`: nur automatisch angelegte Beziehungen dieser Quelle werden
entfernt; manuelle bleiben). Stößt die Neuberechnung der abgeleiteten Gruppen an.

### 5.3 FamilyService

Familie anlegen, Mitglied hinzufügen/entfernen, Paar verknüpfen, zusammenführen,
teilen, sperren. Schreibt während der Übergangszeit für Zweier-Familien `sorg2` mit
(`FAMILY_DUAL_WRITE_SORG2`), damit ein Rollback möglich bleibt.

### 5.4 GroupMembershipService

Leitet Gruppenmitgliedschaften der Bezugspersonen aus den Kindern ab (Klasse, Gruppe,
`child_group`, AGs; nur mit `receives_information`) als `group_user.is_auto_provisioned`.
Manuelle Mitgliedschaften werden nie angefasst; Gruppen-Chats werden mitgepflegt.

### 5.5 ChildPolicy

`view`, `manage`, `reportSick`, `viewHealth` (Sorgerecht oder Verwalten; Art. 9 DSGVO),
`answerFeedback` (nur Sorgerecht, E7), `editGuardians` (nur Verwaltung, E6).
Betreuungspersonal (`edit schickzeiten`) behält den Zugriff.

## 6. Module

### 6.1 Kinder, Schickzeiten, Anwesenheit, Vollmachten

Zugriff über `ChildPolicy`; Kinderlisten über den Resolver (mit Recht).

### 6.2 Pflichtstunden

Einheiten kommen aus dem Resolver. Basis je Familie oder je Kind (E1), geteilte Kinder
getrennt/anteilig/zusammengefasst, optionale Obergrenze und Gruppenfilter.
Die Kontoführung (Sollmodell Standard/ermäßigt/individuell, Salden, Übertrag) speichert
unter `family_key` = kleinste User-ID der Einheit – identisch zur bisherigen Logik.
Beim Lesen wird auch unter den IDs aller Mitglieder gesucht, damit Regeln und Salden
Zusammensetzungswechsel und Rollback überstehen. Abgelaufene Zeiträume werden nur durch
den Jahresabschluss bzw. das Versiegeln vor dem Löschen neu geschrieben.

### 6.3 Reinigung

Fairness-Verteilung je Familie; Termine der ganzen Familie in Übersicht, iCal und Erinnerungen.

### 6.4 Krankmeldungen

An das Kind gebunden; Krankmelden mit `reportSick`, Einsicht mit `viewHealth`
(auch Krankmeldungen des anderen Elternteils). Weitere Berechtigte werden informiert
(Glocke, App-Push, E-Mail; abschaltbar unter Einstellungen › Benachrichtigungen).

### 6.5 Rückmeldungen und Abfragen

`scope`: `child` (je betroffenem Kind der Nachrichtengruppen; die Antwort irgendeines
Sorgeberechtigten genügt, E2/E7), `family` (eine Antwort je Familie) oder `person`.
Im Modus `legacy` wirkt `child` wie `family`. Antworten tragen `child_id`.
Apps ohne Kindauswahl: bei genau einem beantwortbaren Kind wird dieses verwendet,
sonst 422.

### 6.6 Listen und Termine

`booking_scope = child`: jede Buchung/Eintragung gehört zu einem Kind, „nur einmal“ gilt
je Kind über alle Bezugspersonen (auch über Familiengrenzen). `family`: bisheriges
Verhalten. Bezugspersonen des Kindes dürfen die Buchung sehen und absagen.

### 6.7 Lesebestätigungen

`read_receipt_scope`: `family` (bisher; ein Familienmitglied genügt), `person` (jede
Person selbst) oder `child` (je betroffenem Kind genügt die Bestätigung einer
Bezugsperson mit Informationsrecht). Maßgeblich für Anzeige, Erinnerungen, Dashboard und
beide APIs (`ReadReceiptStatusService`). Im Modus `legacy` wirkt `child` wie `family`.

## 7. Familienbildung

### 7.1 Regeln

Zusammenhangskomponenten über gemeinsame Kinder: Einzelperson oder Paar → Familie;
≥ 3 Personen mit identischen Kindern → eine Familie; sonst Klärungsfall.
Gesperrte Familien werden nie verändert.

### 7.2 Klärungsfälle

`php artisan family:review` bzw. Verwaltung › Familien › Klärung.

### 7.3 Automatik

`php artisan family:rebuild [--dry-run] [--user=] [--only-unassigned]` – idempotent;
läuft auch nach UCS-Sync und Import für neu hinzugekommene Personen.

## 8. Import

Schüler-Import: eine Zeile je Kind mit Schüler-ID (E4), Klasse, Gruppe, Stufe, weiteren
Gruppen und bis zu drei Bezugspersonen (Beziehung, Sorgerecht). Bezugspersonen werden per
E-Mail abgeglichen (neue Konten erhalten eine Willkommensmail). Vorschau als Probelauf,
erst nach Bestätigung wird gespeichert; Abgänger optional. Eltern-/Aufnahme-Import legen
Familien statt `sorg2` an.

## 9. API und App

Additiv: `children[].my_rights`, `guardians`, `my_relation`, `GET family`,
`GET user/relations`, `GET children/{child}/guardians`, Rückmeldungs-`scope`/`targets`,
`child_id` bei Antworten und Buchungen, `booking_scope`/`bookable_children` bei Listen,
`read_receipt.scope`. App-API v1: `App\Services\App\Family` delegiert an den Resolver.

## 10. Verwaltungsoberfläche

Kind bearbeiten › Bezugspersonen (Beziehung, Rechte, Gültigkeit, Herkunft, Prüfung);
Verwaltung › Familien (anlegen, zusammenführen, teilen, sperren) und Klärung
(Meldungen „Verbindung ist falsch“, übernommene Beziehungen, Klärungsfälle).
Eltern sehen Familie, Kinder und Rechte in ihren Einstellungen (E3/E6).

## 11. Umschalten und Rollback

1. `family:migrate-from-sorg2`, `family:rebuild --only-unassigned`
2. `family:status` ohne Blocker
3. `FAMILY_RESOLVER=child_centric`
4. Rollback: `FAMILY_RESOLVER=legacy` – `sorg2` ist unverändert bzw. mitgepflegt.

## 12. Migration des Bestands

### 12.1 Ablauf

Alle Schemaänderungen sind additiv; der Modus bleibt `legacy`, bis umgeschaltet wird.

### 12.2 Erweiterung `child_user`

Bestehende Verknüpfungen erhalten alle Rechte und `relation = legal_guardian`;
UCS-Verknüpfungen `source = ucs`.

### 12.3 `sorg2` → Familien

`php artisan family:migrate-from-sorg2 [--dry-run] [--report=] [--sync-groups]`:
konsistente Paare → Familie; einseitige Kopplungen werden als Paar repariert;
Kinder, die jemand nur über den Partner sah, werden direkt verknüpft
(`source = migration`, ungeprüft, E3). Konflikte (Dreiecke, gelöschte Konten) werden nur
gemeldet. `sorg2` bleibt unverändert. Idempotent.

### 12.4 Gruppenmitgliedschaften

`php artisan groups:reconcile-parent-memberships [--dry-run] [--remove-orphans]`
wandelt erklärbare manuelle Klassenmitgliedschaften in abgeleitete um.

## 13. Tests

### 13.1 Szenarien

`tests/Concerns/BuildsFamilies`: Paar, Paar mit Kind nur an einem Elternteil,
Patchwork (A hat Kind X mit B und Kind Y mit C), Dreier-Familie. Charakterisierungstests
laufen in beiden Resolver-Modi.

## 14. Konfiguration

`config/family.php`: `FAMILY_RESOLVER` (`legacy` | `child_centric`),
`FAMILY_DUAL_WRITE_SORG2` (Standard `true`).

## 15. UCS@school

Beziehungen aus UCS kommen mit `source = ucs` über `GuardianshipService::syncFromSource`;
Gruppen über `GroupMembershipService`; neue Personen werden per `FamilyBuilder` zu
Familien (`source = ucs`). Siehe `docs/ucs-kelvin-integration-konzept.md` §9.

## 16. Behobene Fehler (FAM-00)

Mail-Archiv-Filter, fremde Kinder krankmeldbar (Web und API), `ChildController::update`
entfernte andere Eltern und hatte keine Berechtigungsprüfung, Tippfehler bei
Partner-Benachrichtigungen, nicht existierende Spalten (`sorg1`, `posts_id`),
Settings-Gruppe `Care` unter SQLite, doppelte Rückmeldungen möglich.
