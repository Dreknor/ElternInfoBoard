# Modul „Elternabfragen"

Stand: 18.10.2026 · Umsetzung des Konzepts „Digitale Elternabfragen im ElternInfoBoard"

Der Code verweist mit `§` auf diese Abschnitte. Optimierungen gegenüber dem
Ausgangskonzept sind mit **(O)** markiert.

---

## 1. Überblick

Verbindlicher Ablauf (Konzept Kap. 1/4):

| Prozess | Umsetzung |
|---|---|
| A Vorlage erstellen und prüfen | `TemplateService` – Entwurf → In Prüfung → Freigegeben / Zur Überarbeitung; neue Versionen ersetzen alte |
| B Abfrage vorbereiten und freigeben | `InquiryService` – Entwurf → Wartet auf Freigabe → Freigegeben (geplant) → Läuft → Abgeschlossen |
| C Abfrage durch Eltern | `ParentInquiryController`, `ResponseService`, App-API v1 |
| D Rücklauf überwachen und erinnern | `InquiryController@show`, `ReminderService`, `DeliveryService` |
| E Antwort prüfen und exportieren | `AssignmentAdminService`, `ExportService` |

Zeitgesteuert: `php artisan elternabfragen:process` (alle 15 Minuten im Scheduler) –
geplante Abfragen starten, automatische Erinnerungen, automatischer Abschluss,
Ablauf von Exporten, Löschfristen. `--dry-run` zeigt fällige Löschungen.

## 2. Datenmodell

| Tabelle | Inhalt |
|---|---|
| `inquiry_templates` | Identität einer Vorlage (Titel, Kategorie, Archiv) |
| `inquiry_template_versions` | Versionierte Inhalte: Zweck, Elterninformation, zuständige Stelle, Zielgruppenhinweis, Standardfrist, Aufbewahrung, sensibel, Felder (JSON), Prüfvermerke. Nach Freigabe unveränderlich (Model-Guard) |
| `inquiries` | Abfrage: Vorlagenversion, Titel, Einführung, Start, Frist, Zielgruppe, verantwortliche Person, Kanäle, Erinnerungen, Mitarbeitersicht, Korrekturfenster, Aufbewahrung, Freigabe, erwartete Anzahl, Abschluss/Löschung. Inhalte nach Freigabe gesperrt (Model-Guard) |
| `inquiry_assignments` | Auftrag je Kind: Status, Klasse zum Startzeitpunkt, gültige Antwort, Nachfrist, Korrekturfreigabe, Problem, Bearbeitungshinweis, Erinnerungszähler |
| `inquiry_recipients` | berechtigte Konten je Auftrag inkl. Zustellstatus |
| `inquiry_drafts` | Entwurf je Konto (gilt nicht als Rückmeldung) |
| `inquiry_responses` | unveränderliche Antwortversionen (Kap. 7.2) |
| `inquiry_deliveries` | Protokoll jeder Benachrichtigung/Erinnerung (Zeit, Kanal, Empfänger, Ergebnis, manuell/automatisch) |
| `inquiry_events` | Prozessprotokoll (Freigaben, Fristen, Nachfristen, Korrekturfreigaben, Prüfungen, Exporte, Löschung) |
| `inquiry_exports` | Exportprotokoll mit Ablaufdatum |

### 2.1 Felder

Typen (`InquiryFieldType`): Einwilligung, Zustimmung/Ablehnung, Mehrfachauswahl, Auswahl
einer Option, Dropdown, Text, Textarea, Datum, Datei, Auswahl eines eigenen Kindes
(sorgeberechtigte Kinder des antwortenden Kontos; gespeichert als `{id, name}`, damit die
Antwort nach Löschung/Umbenennung lesbar bleibt) sowie **(O)** „Abschnitt/Hinweis"
zur Gliederung in kurze Abschnitte. Je Feld: Pflicht, Format (E-Mail, Telefon, PLZ, Zahl),
Mindest-/Höchstlänge (bei Mehrfachauswahl: Anzahl), eigene Fehlermeldung.
Feldschlüssel bleiben über Versionen stabil, damit Exporte vergleichbar sind.

Excel-Import (`TemplateImporter`): Spaltenköpfe der ersten Zeile werden zu Feldern; vorhandene
Werte bestimmen den Typ (Ja/Nein, Datum, ≤ 10 wiederkehrende Werte → Auswahl, lange Texte →
Textarea). Spalten zur Identifikation des Kindes (Name, Vorname, Klasse …) entfallen.
Elternvorschau für gespeicherte Vorlagenversionen und für den ungespeicherten Editor-Stand.

Einwilligungen: Werte `granted`/`denied`. „Pflicht" bedeutet *Entscheidung erforderlich* –
die Einwilligung selbst bleibt freiwillig **(O)**. Der vollständige Text liegt in der
Vorlagenversion und fließt in den Formular-Hash ein.

## 3. Mandatsmodell (Kap. 7.1)

**(O)** Kein eigenes Mandatsmodell: Die Rechte aus dem kind-zentrierten Familienmodell
(`child_user`, siehe `kind-zentriertes-familienmodell-konzept.md`) decken das Konzept ab:

| Konzept | Umsetzung |
|---|---|
| Sorgeberechtigung / Abgabe von Erklärungen | `has_custody` – sehen, Antworten lesen, abgeben, korrigieren |
| Leseberechtigung | jede gültige Beziehung (z. B. Großeltern) – Abfrage und Status sehen, keine Antwortinhalte |
| aktives/beendetes Mandat, zeitliche Gültigkeit | `valid_until` – abgelaufene Beziehungen zählen nicht |

Prüfung serverseitig bei jedem Zugriff (`InquiryAccess`). Nicht zugängliche Aufträge
liefern **404** (keine Preisgabe der Existenz). Eine gültige Antwort eines
Sorgeberechtigten genügt je Kind (wie E2/E7); weitere Sorgeberechtigte werden über
Abgaben informiert **(O)** (getrennte Haushalte). Gleichzeitige Abgaben werden per
Zeilensperre serialisiert; eine zweite Abgabe gilt als Korrektur oder wird mit 409 abgelehnt.

**Gemeinsame Erklärung (Vorlagenoption `requires_all_guardians`):** Alle weiteren Konten mit
aktuell gültigem Sorgerecht bestätigen die gültige Antwortversion (`inquiry_confirmations`,
je Version; `GuardianConfirmation`). Bis dahin Status „Bestätigung ausstehend“; Erinnerungen
gehen nur an die Fehlenden. Wer nicht einverstanden ist, darf bis zur Frist korrigieren –
jede neue Version erfordert erneut alle Bestätigungen.

Empfänger werden beim **Start** ermittelt. Kinder ohne sorgeberechtigtes Konto erhalten
sofort den Status „Technisches Problem" **(O)**.

## 4. Zielgruppen

Klassen, mehrere Gruppen, Jahrgang (Gruppe „Klassenstufe …" aus dem Schülerimport über
`child_group`) oder gesamte Schule (alle aktiven Kinder). **(O)** „Zielgruppe abgleichen"
ergänzt nachträglich hinzugekommene Kinder (nie Entfernen).

## 5. Rücklauf, Erinnerungen, Benachrichtigungen (Kap. 6.4, 8)

Status je Kind: offen, in Bearbeitung, eingereicht, Korrektur eingereicht, technisches
Problem. Erinnerungen sind kein Status, sondern Zustellungen.

Kanäle: E-Mail (je Abfrage abschaltbar) und Glocke inkl. App-/Web-Push nach der
Nutzerwahl (neue Kategorie „Elternabfragen" in den Benachrichtigungseinstellungen).
E-Mails enthalten keine Antwortinhalte **(O)**.

Regeln (`ElternabfragenSetting`, Verwaltung › Elternabfragen › Regeln):

* erste Erinnerung frühestens nach 48 h, weitere höchstens alle 24 h, Höchstzahl 3 **(O)**
* keine Erinnerung nach Einreichung oder nach Fristende ohne Nachfrist
* Ausnahme von der Wartezeit bei technischem Problem oder Nachfrist
* automatisch: einmal X Tage vor Fristende; nach Fristende einmal je gesetzter Nachfrist
* Zähler werden beim Einplanen gesetzt, damit eine verzögerte Queue nicht doppelt erinnert **(O)**

Zustellfehler (keine E-Mail, Versandfehler, kein Konto) markieren den Fall als Problem
und informieren die verantwortliche Person.

## 6. Korrekturen und Audit (Kap. 7.2, 7.3)

Jede Einreichung erzeugt eine `inquiry_responses`-Zeile mit Vorlagenversion, Kind,
absendendem Konto, Zeitpunkt, Version, vollständigem Datensatz, `form_hash`
(SHA-256 über Titel, Einführung, Vorlagenversion und Felder inkl. Einwilligungstexte),
`answer_hash`, Kanal, gekürzter IP (letztes Oktett entfernt) und Bezug zur Vorversion
mit Korrekturgrund. Inhalte sind per Model-Guard unveränderlich; nur Prüfvermerk und
„gültig" ändern sich.

Eltern korrigieren selbst innerhalb des Zeitfensters (Standard 7 Tage ab Ersteinreichung),
danach nur nach Freigabe durch das Sekretariat. Klärungen werden als Prüfstatus
„Klärung nötig" bzw. Bearbeitungshinweis dokumentiert.

## 7. Rollen

| Recht | Rolle im Konzept |
|---|---|
| `manage elternabfragen` | Sekretariat |
| `approve elternabfragen` | Schulleitung / benannte Stelle |
| `view elternabfragen answers` | Einsicht in Antwortdetails (Rolle + Zweckbindung) |
| `export elternabfragen` | Export mit Berechtigungsprüfung |
| `read elternabfragen` | Mitarbeiter-Dashboard |

**(O)** Vier-Augen-Prinzip (abschaltbar) für Vorlagen- und Abfragefreigaben.
**(O)** Die Rücklaufübersicht zeigt nie Antwortinhalte. Mitarbeitende sehen nur
aktuelle, **geprüfte** Antworten von Abfragen mit „Für Mitarbeitende lesbar".

## 8. Export (Kap. 9)

Auswahl (Abfrage/Klasse, Felder, offene Fälle, Format XLSX/CSV, Ziel) → Vorschau
(Datensätze, Felder, Antwortversion, offen/fehlend/Klärung/ungeprüft, Ziel und
verantwortliche Person) → Datei auf privatem Speicher mit Ablaufdatum. Erstellen und
jeder Download werden protokolliert. **(O)** Formeln aus Freitext werden neutralisiert
(CSV-Injection); Spalte „Schüler-ID" erleichtert die manuelle Übertragung.

## 9. Aufbewahrung und Löschung (Kap. 7.4)

Aufbewahrungsfrist je Vorlage (sonst Standard) wird bei Anlage in die Abfrage kopiert.
Geplante und laufende Abfragen können jederzeit (auch vor Fristende) mit optionaler
Begründung beendet werden; das Protokoll vermerkt „vorzeitig“ bzw. „vor dem Start“.
Laufende Abfragen werden 30 Tage nach Fristende automatisch abgeschlossen **(O)**, damit
die Frist zuverlässig beginnt. Danach löscht `elternabfragen:process`: Antworten,
Uploads, Entwürfe, Empfänger, Zustellprotokoll, fallbezogene Ereignisse, Exportdateien.
Erhalten bleiben Abfrage und anonyme Statistik (Anzahl je Status) im Ereignisprotokoll.

## 10. App-API v1

Präfix `/api/v1/elternabfragen` (hinter `module:Elternabfragen`):

| Methode | Pfad | Zweck |
|---|---|---|
| GET | `/` (`?filter=open`) | Aufträge zu den eigenen Kindern |
| GET | `/{id}` | Details: Zweck, Felder, Entwurf, gültige Antwort, Versionen, Rechte |
| PUT | `/{id}/draft` | Entwurf speichern |
| DELETE | `/{id}/draft` | Entwurf verwerfen |
| POST | `/{id}/submit` | absenden/korrigieren (JSON oder multipart) |
| GET | `/{id}/pdf` | PDF-Bestätigung |
| GET | `/{id}/files/{key}` | eigene Datei |

Außerdem: `parent/todo` und `parent/dashboard` enthalten Aufgaben vom Typ `inquiry`
(`target: {type: inquiry, id}`); Push-Ziele `/elternabfragen/{id}` → `{type: inquiry}`.

## 11. Einführung

1. `php artisan migrate` (legt Tabellen, Rechte, Modul [inaktiv] und Regeln an)
2. Rechte an Rollen vergeben (Sekretariat, Schulleitung, Lehrkräfte)
3. Modul unter Einstellungen › Module aktivieren
4. Hilfe: `resources/help/elternabfragen*.md`, `verwaltung-elternabfragen.md`
