# Elternabfragen verwalten

Das Modul ersetzt wiederkehrende Papierabfragen. Der verbindliche Ablauf:

1. **Vorlage** anlegen oder auswählen und prüfen lassen.
2. **Abfrage** vorbereiten und zur **Freigabe** einreichen.
3. Nach der Freigabe erzeugt das System die Aufträge **je Kind** und benachrichtigt die sorgeberechtigten Elternkonten.
4. **Rücklauf** überwachen, erinnern, Einzelfälle klären.
5. Ergebnisse **kontrolliert exportieren**.

Zu finden unter **Verwaltung › Elternabfragen**.

## Rechte

| Recht | Wofür |
|---|---|
| `manage elternabfragen` | Vorlagen und Abfragen vorbereiten, Rücklauf, Erinnerungen, Nachfristen (Sekretariat) |
| `approve elternabfragen` | Vorlagen und Abfragen freigeben, Regeln einstellen (Schulleitung) |
| `view elternabfragen answers` | Antwortinhalte einsehen und prüfen |
| `export elternabfragen` | Exporte erstellen und herunterladen |
| `read elternabfragen` | Mitarbeitende: geprüfte Antworten lesen |

Das Modul muss unter **Einstellungen › Module** aktiviert werden.

## 1. Vorlagen

**Vorlagen › Neue Vorlage**. Eine Vorlage enthält Titel, Kategorie, **Zweck** (Pflicht – wofür die Angaben gebraucht werden), Elterninformation, zuständige Stelle, Standardfrist, Aufbewahrungsfrist und die Formularfelder.

**Feldtypen:** Zustimmung/Ablehnung, Einwilligung (mit vollständigem Text), Mehrfachauswahl, Auswahl einer Option, Dropdown, Text ein-/mehrzeilig, Datum, Datei-Upload, **Auswahl eines eigenen Kindes** (Eltern wählen eines der Kinder, für die sie sorgeberechtigt sind; gespeichert wird der Name) sowie **Abschnitt/Hinweis** zur Gliederung. Jedes Feld kann Pflichtfeld sein und eine Formatprüfung (E-Mail, Telefon, PLZ, Zahl), Mindest-/Höchstlänge und eine eigene, verständliche Fehlermeldung bekommen.

**Aus Excel-Liste übernehmen:** Im Formular-Editor eine Excel-, ODS- oder CSV-Datei wählen. Jede Spaltenüberschrift der ersten Zeile wird zu einem Feld. Stehen darunter bereits Werte (z. B. eine bisher auf Papier geführte Liste), wird der Feldtyp abgeleitet: Ja/Nein, Datum, wenige wiederkehrende Werte als Auswahl, lange Texte als mehrzeiliges Textfeld. Spalten wie Name, Vorname oder Klasse werden nicht übernommen – die Zuordnung zum Kind erfolgt automatisch. Übernommene Felder bitte prüfen und anpassen.

**Vorschau:** „Vorschau (ohne Speichern)“ im Editor öffnet den aktuellen Stand in einem neuen Tab aus Sicht der Eltern. Gespeicherte Vorlagenversionen haben auf ihrer Detailseite den Button „Elternvorschau“.

> Nur erheben, was für den Zweck nötig ist (Datenminimierung).

Mit **Zur Prüfung einreichen** geht die Vorlage an die Schulleitung. Kennzeichne Vorlagen mit Gesundheitsdaten oder Einwilligungen als **sensibel** – dann ist eine datenschutzrechtliche Zusatzprüfung Pflicht.

Freigegebene Vorlagen sind **unveränderlich**. Änderungen: **Neue Version anlegen** – laufende Abfragen behalten ihre Version.

## 2. Abfrage vorbereiten

**Abfragen & Rücklauf › Neue Abfrage** (oder „Abfrage erstellen" an einer Vorlage):

- Titel, Einführungstext, **Startzeitpunkt** und **Rückgabefrist**
- **Zielgruppe**: einzelne Klassen, mehrere Gruppen, Jahrgang (Gruppe „Klassenstufe …") oder die gesamte Schule
- verantwortliche Person, Kanäle (E-Mail, App/Web-Push), automatische Erinnerungen, Zeitfenster für Selbstkorrektur
- „Für Mitarbeitende lesbar": nur dann sehen Lehrkräfte geprüfte Antworten (Zweckbindung)

Über **Elternvorschau** siehst du das Formular aus Sicht der Eltern. Vor dem Start zeigt die Abfrage die **erwartete Anzahl** Kinder und Elternkonten sowie Kinder **ohne sorgeberechtigtes Konto** und Konten ohne E-Mail-Adresse.

Nach dem Einreichen gibt die Schulleitung frei. Liegt der Start in der Zukunft, startet die Abfrage automatisch.

### Nach dem Start

Eine gestartete Abfrage kann **inhaltlich nicht mehr geändert** werden. Möglich sind:

- **Frist ändern** (mit Begründung, wird protokolliert)
- Einstellungen (Kanäle, Erinnerungen, Zuständigkeit, Mitarbeitersicht)
- **Zielgruppe abgleichen**: neu hinzugekommene Kinder werden ergänzt und benachrichtigt
- **Als neue Abfrage kopieren**, wenn sich der Inhalt ändern muss
- **Abschließen** – danach keine Abgaben und Erinnerungen mehr (automatisch 30 Tage nach Fristende)

## 3. Rücklauf

Die Übersicht zeigt je Kind Klasse, **Status**, berechtigte Konten, letzte Benachrichtigung/Erinnerung und Hinweise – **ohne Antwortinhalte**. Filter: Status, Klasse, Nachfrist, Suche nach Kind oder Elternkonto.

| Status | Bedeutung |
|---|---|
| Offen | keine gültige Antwort |
| In Bearbeitung | Entwurf begonnen, nicht abgesendet |
| Eingereicht | vollständige Antwort liegt vor |
| Korrektur eingereicht | neue Antwortversion abgegeben |
| Technisches Problem | Zustellung oder Bearbeitung nicht möglich (z. B. kein Konto, keine E-Mail) |

Bei Zustellproblemen erhält die verantwortliche Person eine Mitteilung.

### Erinnern

**Alle offenen erinnern** oder einzelne Fälle auswählen. Standardregeln (änderbar unter **Regeln**): erste Erinnerung frühestens nach 48 Stunden, danach höchstens alle 24 Stunden, keine Erinnerung nach erfolgreicher Abgabe. Bei technischem Problem oder Nachfrist entfällt die Wartezeit. Zeitpunkt, Kanal und Empfänger werden protokolliert.

### Einzelfall („Details")

- **Nachfrist** setzen (mit Grund)
- **Technisches Problem** melden bzw. als gelöst markieren
- **Bearbeitungshinweis** – z. B. zur Klärung widersprüchlicher Angaben
- **Korrektur freigeben**, wenn das Zeitfenster der Eltern abgelaufen ist
- Antwortversionen ansehen und **prüfen** (Geprüft / Klärung nötig) – nur mit Recht „Antwortinhalte einsehen"

## 4. Export

**Export** in der Abfrage: Ebene (gesamte Abfrage oder Klasse), Felder, Format (Excel oder CSV) und **Ziel** wählen. Die **Vorschau** zeigt Anzahl der Datensätze, Felder, Antwortversion, fehlende oder auffällige Angaben, Ziel und verantwortliche Person. Jeder Export und jeder Download wird protokolliert; die Datei wird nach Ablauf (Standard 7 Tage) gelöscht. Die Übertragung in die Schulverwaltung erfolgt manuell.

## Aufbewahrung

Nach Ablauf der Aufbewahrungsfrist (ab Abschluss) werden Antworten, Uploads, Exportdateien und personenbezogene Protokolldaten automatisch gelöscht. Erhalten bleiben die Abfrage selbst und eine anonyme Statistik.
