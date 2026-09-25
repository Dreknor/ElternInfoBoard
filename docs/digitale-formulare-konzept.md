# Konzept: Digitale Formulare & Stammdaten-Abgleich im Schulzentrum

Stand: 25.09.2026 · Status: Entwurf zur Diskussion

## 1. Ausgangslage

Das Schulzentrum besteht aus einer Grundschule und einer Oberschule.

| | Grundschule | Oberschule |
|---|---|---|
| Kommunikation | ElternInfoBoard (EIB) | SDUI |
| EIB-Konto der Eltern | ja | ja (für Listen und Pflichtstunden) |
| Stammdaten | externe Schulverwaltungssoftware, **ohne Import und ohne API** | dieselbe |

**So läuft es heute auf Papier:**

```
Sekretariat druckt → Klassenleitung verteilt → Eltern füllen aus/unterschreiben
      ↑                                                    ↓
      └── Rückfrage bei Fehlern ← Sekretariat vergleicht ← Klassenleitung sammelt
          (Klassenleitung → Eltern → …)    jeden Zettel mit der Stammdaten-Software
```

**Wo der Aufwand entsteht:**

1. **Verteilen und Einsammeln:** Mehrere Personen sind beteiligt, Zettel gehen verloren, niemand hat einen Überblick über den Stand.
2. **Abgleichen:** Das Sekretariat vergleicht *jeden* Zettel Feld für Feld mit der Software, obwohl sich meist nichts geändert hat.
3. **Fehlende Angaben:** Eine vergessene Unterschrift oder ein fehlendes Feld startet den ganzen Kreislauf von vorn.
4. **Übertragen:** Die Änderungen werden von Hand in die Software eingegeben. Das bleibt auch in Zukunft so, weil es keine Schnittstelle gibt.

**Rahmenbedingung:** Nur das ElternInfoBoard darf angepasst werden. Punkt 4 lässt sich deshalb nicht automatisieren. Die Punkte 1 bis 3 lassen sich aber fast vollständig beseitigen, und Punkt 4 lässt sich auf die wirklich geänderten Felder beschränken.

## 2. Leitidee

> **Das Sekretariat überträgt nur noch eine Änderungsliste statt jeden Zettel zu prüfen.**

1. Das EIB kennt für jedes Kind den bisherigen Stand der Daten. Dieser kommt entweder aus einem Export der Stammdaten-Software (Variante A) oder aus der letzten bestätigten Abgabe (Variante B).
2. Die Eltern sehen das Formular **vorausgefüllt**. Sie bestätigen „stimmt so“ oder korrigieren einzelne Felder.
3. Das EIB prüft bei der Abgabe, ob alle Pflichtfelder ausgefüllt und bestätigt sind. **Unvollständige Abgaben sind technisch nicht möglich.**
4. Das Sekretariat bekommt eine **Liste nur der geänderten Felder** (alt → neu). Es überträgt sie in die Stammdaten-Software und hakt jede Zeile ab.
5. Wer noch nicht abgegeben hat, sieht das Sekretariat (alle Klassen) bzw. die Klassenleitung (eigene Klasse) live. Erinnerungen verschickt das System automatisch.

## 3. Was das EIB schon mitbringt

Das Konzept baut auf vorhandenen Bausteinen auf:

| Baustein im Code | Nutzen für Formulare |
|---|---|
| `Child` mit `external_id`, `class_id`, `status` | Kind als Bezugsobjekt; `external_id` dient als Schlüssel zur Stammdaten-Software |
| `ChildGuardian` (`child_user`) mit `has_custody`, `can_manage` | Wer darf für welches Kind unterschreiben (Sorgerecht) |
| Schüler-Import (`ImportController`, ODS/XLSX/CSV mit Vorschau) | Muster für den Import eines Stammdaten-Exports |
| Rückmeldungen/Abfragen pro Kind (`rueckmeldungen.scope = child`, `abfrage_answers.child_id`) | Vorhandene einfache Abfragen; Ausgangspunkt bzw. Übergangslösung |
| `owen-it/laravel-auditing` | Nachweis, wer wann was bestätigt oder geändert hat |
| `barryvdh/laravel-dompdf`, `maatwebsite/excel` | PDF für die Schülerakte, Excel-/ODS-Export der Änderungslisten |
| Push/Mail-Benachrichtigungen, `ReminderLog` | Erinnerungen an säumige Eltern |
| `spatie/laravel-permission` | Neue Rechte, z. B. `manage forms` und `view form status` |

## 4. Fachliches Konzept

### 4.1 Formulartypen

| Typ | Beispiele | Vorausgefüllt | Braucht Übertragung in die Stammdaten-Software |
|---|---|---|---|
| **Stammdatenkontrolle** | Adresse, Telefonnummern, Notfallkontakte, Abholberechtigte, Krankenkasse | ja | ja |
| **Einwilligung** | Fotos/Website, Weitergabe von Kontaktdaten, Medienausleihe | optional (Vorjahr) | teilweise (Kennzeichen) |
| **Anmeldung/Auswahl** | Ganztag/AG, Religion/Ethik, Wahlpflicht, Klassenfahrt | nein | teilweise |
| **Kenntnisnahme** | Hausordnung, Belehrungen, Infektionsschutz | nein | nein |
| **Änderungsmeldung (ständig offen)** | „Wir sind umgezogen“, neue Handynummer | ja | ja |

Die ständig offene **Änderungsmeldung** ersetzt zusätzlich formlose Zettel und Mails über das Jahr hinweg. Sie läuft durch dieselbe Änderungsliste.

### 4.2 Rollen

| Rolle | Aufgaben |
|---|---|
| **Sekretariat / Verwaltung** (`manage forms`) | Vorlagen pflegen, Formulare ausgeben, Stammdaten-Export importieren, Änderungslisten abarbeiten, Papier-Rückläufer erfassen, Rückfragen stellen |
| **Klassenleitung** (`view form status`, auf eigene Klasse beschränkt) | Rücklaufstatus der eigenen Klasse sehen, gezielt erinnern; sieht keine Inhalte (Datenminimierung) |
| **Sorgeberechtigte** (`has_custody`) | Formular pro Kind ausfüllen, bestätigen, später einsehen (PDF) |
| **Weitere Bezugspersonen** (ohne Sorgerecht) | nur Einsicht, falls überhaupt; keine Unterschrift |

### 4.3 Ablauf

```
[Sekretariat]                    [EIB]                                  [Eltern]
Stammdaten-Export (ODS/CSV) ──► Import + Abgleich über external_id
Vorlage wählen, Zielgruppe  ──► Ausgabe anlegen (pro Kind)       ──► Push/Mail + Dashboard-Kachel
Frist setzen                                                          Formular vorausgefüllt öffnen
                                                                      „Alles korrekt“ oder Felder ändern
                                Pflichtfeld-/Formatprüfung      ◄──  Bestätigen („digitale Unterschrift“)
                                Diff berechnen (alt ↔ neu)
                                Status: eingereicht
                                automatische Erinnerungen bis Frist ──► säumige Eltern
Änderungsliste öffnen      ◄──  nur geänderte Felder, gruppiert pro Kind
In Stammdaten-Software
übertragen, Zeile abhaken  ──►  Status: übertragen, Audit-Eintrag
Bei Unklarheit: „Rückfrage“ ──► Status: Rückfrage                ──► Eltern korrigieren direkt
```

**Statusmodell je Kind und Formular:**
`offen → in Bearbeitung (Entwurf) → eingereicht → [Rückfrage ↔ eingereicht] → geprüft → übertragen / abgeschlossen`

### 4.4 Die „digitale Unterschrift“

- Die Eltern sind am EIB angemeldet. Sie bestätigen ausdrücklich, z. B. mit „Ich bestätige die Richtigkeit der Angaben / erteile die Einwilligung“. Gespeichert werden Nutzer, Zeitpunkt, Formularversion und die bestätigten Werte (Audit).
- Rechtlich ist das eine **einfache elektronische Signatur**. Für die meisten Schulformulare genügt sie: Einwilligungen nach DSGVO müssen *nachweisbar* sein (Art. 7 Abs. 1), eine Schriftform ist dafür nicht vorgeschrieben. Stammdatenmeldungen sind ohnehin formfrei.
- **Einschränkung:** Wo Landesrecht oder Schulträger ausdrücklich die Schriftform verlangen, bleibt es bei Papier. Dafür kann das EIB ein vorausgefülltes PDF erzeugen, das nur noch unterschrieben wird. Das muss vorab mit Schulleitung und Datenschutzbeauftragter geklärt werden (siehe Kapitel 8).
- **Gemeinsames Sorgerecht**, pro Vorlage einstellbar:
  - *Eine Person genügt* (Standard für Stammdaten und Alltagsangelegenheiten).
  - *Eine Person bestätigt „im Einvernehmen mit dem anderen Elternteil“* (übliche Praxis bei Papierformularen).
  - *Alle Sorgeberechtigten müssen bestätigen*, z. B. bei Einwilligungen mit größerer Tragweite. Das Formular bleibt dann „teilweise bestätigt“, bis alle `has_custody`-Personen bestätigt haben.

### 4.5 Woher kommen die Vergleichsdaten? (Kernfrage)

Die Stammdaten-Software hat weder Import noch API. Fast alle Schulverwaltungsprogramme können aber **Listen oder Berichte als Excel/CSV exportieren** (für Serienbriefe, Klassenlisten usw.). Das muss geprüft werden.

**Variante A – Export-Spiegel (empfohlen, falls ein Export möglich ist)**
- Das Sekretariat exportiert vor einer Ausgabe eine Liste mit Schüler-ID und den relevanten Feldern und lädt sie ins EIB hoch.
- Das EIB ordnet die Zeilen über `external_id` zu (wie beim Schüler-Import mit Vorschau und Fehlerliste) und legt einen **Snapshot** an.
- Die Formulare werden aus dem Snapshot vorausgefüllt. Der Diff wird gegen den Snapshot gerechnet.
- Der Snapshot wird nach Abschluss der Ausgabe gelöscht (Datenminimierung).

**Variante B – Selbstlernender Spiegel (falls kein Export möglich ist)**
- Die erste Ausgabe ist eine vollständige Abfrage ohne Vorausfüllung. Das Sekretariat gleicht wie bisher einmalig vollständig ab.
- Das EIB merkt sich die zuletzt **bestätigten** Werte. Ab der zweiten Ausgabe wird daraus vorausgefüllt, und der Diff läuft gegen die letzte Abgabe.
- Risiko: Ändert das Sekretariat Daten direkt in der Software (z. B. nach einem Anruf), weicht der Spiegel ab. Gegenmaßnahme: Beim Abhaken in der Änderungsliste kann das Sekretariat den übernommenen Wert korrigieren. Die Änderungsmeldung (4.1) wird als einziger Kanal für Änderungen etabliert.

In beiden Varianten entfällt das Vergleichen **jedes** Zettels. Übrig bleiben nur echte Änderungen.

### 4.6 Sekretariats-Ansicht „Änderungsliste“

| Kind (Klasse) | Feld | Bisher | Neu | Gemeldet von / am | Übertragen |
|---|---|---|---|---|---|
| Mia M. (5b) | Telefon Mutter | 0351 123 | 0171 456 | S. Muster, 12.10. | ☐ |
| Mia M. (5b) | Straße | Hauptstr. 1 | Lindenweg 3 | S. Muster, 12.10. | ☐ |
| Ben K. (2a) | Fotoerlaubnis Website | ja | **nein** | T. Kurz, 13.10. | ☑ 14.10. (Sekretariat) |

- Filter: Klasse, Schulteil (GS/OS), Formular, „nur offene“.
- Export als ODS/XLSX oder Druckliste, falls lieber am zweiten Bildschirm gearbeitet wird.
- „Keine Änderungen“-Abgaben erscheinen nicht in der Liste, zählen aber für den Rücklauf.
- Pro Kind gibt es ein PDF (Formular + Bestätigungsnachweis) für die digitale oder gedruckte Schülerakte.

### 4.7 Rücklauf und Erinnerungen

- Ampel pro Klasse (abgegeben / offen / Rückfrage) für Sekretariat und Klassenleitung.
- Automatische Erinnerungen, z. B. 7 Tage, 2 Tage und 1 Tag vor der Frist (Push, Mail). Das Muster aus `SendAttendanceQueryReminders`/`ReminderLog` wird wiederverwendet.
- Nach Fristablauf: Liste der Säumigen für die Klassenleitung, damit sie gezielt nachhaken kann statt alle einzusammeln.

### 4.8 Besonderheit Oberschule (SDUI + EIB)

- Formulare laufen für **das ganze Schulzentrum** über das EIB. SDUI bleibt Kommunikationskanal der Oberschule.
- Risiko: Oberschul-Eltern öffnen das EIB selten. Gegenmaßnahmen:
  - Mail- und Push-Benachrichtigung aus dem EIB mit **Direktlink** zum Formular (nach Login direkt dorthin).
  - Die Klassenleitung schickt in SDUI einen kurzen Hinweis mit Link („Bitte bis … im ElternInfoBoard bestätigen“). Das ist organisatorisch, eine Schnittstelle ist nicht nötig.
  - Formular-Kachel prominent auf dem Dashboard, solange etwas offen ist.
- Voraussetzung: Die Oberschüler sind im EIB als `Child` mit Klasse, `external_id` und Sorgeberechtigten-Zuordnung angelegt. Falls das noch fehlt, geht es einmalig über den vorhandenen Schüler-Import.

### 4.9 Fallback für Eltern ohne digitale Möglichkeit

- Das Sekretariat kann eine Papierabgabe **im Namen der Eltern erfassen**. Das wird im Audit als „erfasst durch Sekretariat, Papiervorlage“ markiert.
- Das EIB druckt dafür ein vorausgefülltes PDF, damit auch auf Papier nur Änderungen eingetragen werden.
- Mehrsprachigkeit ist bei Bedarf möglich, weil Vorlagentexte pro Sprache hinterlegt werden können (spätere Ausbaustufe).

## 5. Technisches Konzept (Skizze)

### 5.1 Neues Modul „Formulare“ statt Erweiterung der Rückmeldungen

Die bestehenden Abfragen (`rueckmeldungen` + `abfrage_options`) hängen an Nachrichten. Ihnen fehlen Vorausfüllung, Statusmodell, Diff und Übertragungs-Tracking. Ein eigenes Modul ist sauberer. Es übernimmt die Konzepte „Antwort pro Kind“ und „Sorgerecht prüfen“. Eine Nachricht kann weiterhin auf ein Formular verlinken.

### 5.2 Datenmodell

```
form_templates            id, title, description, school_part (gs|os|both), version,
                          signature_mode (one|one_consent|all_custodians),
                          requires_written_form (bool), is_active

form_fields               id, template_id, key, label, type
                          (text|textarea|email|phone|date|select|checkbox|radio|
                           address|file|section|confirmation),
                          options (json), required, validation (json),
                          prefill_key (nullable, z. B. "telefon_mutter"),
                          sort, sensitive (bool → verschlüsselte Speicherung)

form_campaigns            id, template_id, title, starts_at, deadline,
                          target (json: Klassen/Gruppen/Kinder), created_by,
                          snapshot_import_id (nullable), reminder_plan (json)

master_data_imports       id, uploaded_by, filename, imported_at, row_count,
                          purge_after                      (Variante A)
master_data_values        import_id, child_id, key, value (encrypted)

form_submissions          id, campaign_id, child_id, status, submitted_by,
                          submitted_at, confirmed_all_at, no_changes (bool),
                          entered_by_office (bool), office_note

form_submission_confirmations
                          submission_id, user_id, confirmed_at, ip_hash, statement_text

form_values               submission_id, field_key, previous_value, value
                          (encrypted if sensitive), changed (bool),
                          transferred_at, transferred_by, transferred_value
```

- Variante B braucht keine eigenen Tabellen. `previous_value` kommt aus dem letzten `form_values`-Eintrag desselben `prefill_key` für das Kind.
- Verschlüsselte Felder mit Laravel-Cast `encrypted`, vor allem für Gesundheitsdaten nach Art. 9 DSGVO (Allergien, Medikamente).
- Alle Status- und Wertänderungen laufen über `Auditable`.

### 5.3 Komponenten

| Komponente | Inhalt |
|---|---|
| Vorlagen-Editor | Felder anlegen/sortieren, Pflichtfelder, `prefill_key` wählen, Vorschau. Neue Version bei Änderung, laufende Ausgaben behalten ihre Version |
| Stammdaten-Import | Upload, Spalten-Mapping auf `prefill_key` (Mapping speichern), Vorschau mit nicht zuordenbaren Zeilen |
| Eltern-Ansicht | Liste offener Formulare pro Kind, vorausgefülltes Formular, Buttons „Alles korrekt“ und „Änderungen speichern & bestätigen“, Entwurf speichern |
| Rücklauf-Dashboard | Ampel je Klasse, Drill-down, „Erinnerung senden“ |
| Änderungsliste | siehe 4.6, inkl. Export |
| PDF-Erzeugung | pro Abgabe (dompdf), Sammel-PDF je Klasse |
| Jobs | Erinnerungen, Löschung abgelaufener Snapshots, Archivierung/Löschfristen |
| Rechte | `manage forms`, `view form status`, `view form content` |

### 5.4 Aufwandsschätzung (grob)

| Stufe | Umfang | Aufwand |
|---|---|---|
| MVP | Vorlagen, Ausgabe, Ausfüllen pro Kind, Bestätigung, Rücklauf-Dashboard, Erinnerungen, PDF, Papier-Erfassung | ca. 8–12 PT |
| Stammdaten-Abgleich | Import (Variante A) bzw. Selbstlern-Spiegel (B), Diff, Änderungsliste mit Abhaken | ca. 5–8 PT |
| Komfort | mehrere Sorgeberechtigte, Rückfrage-Workflow, Datei-Upload, wiederkehrende Ausgaben, Vorlagenbibliothek | ca. 5–8 PT |

## 6. Einführung in Stufen

1. **Klärung (vor der Umsetzung):** Welche Exporte bietet die Stammdaten-Software? Welche Formulare brauchen zwingend Schriftform? Datenschutz-Folgenabschätzung bzw. Verzeichnis anpassen (Kapitel 8). Sind alle Oberschüler mit Sorgeberechtigten im EIB?
2. **Pilot:** ein einfaches Formular (z. B. Fotoeinwilligung oder Kenntnisnahme) in ein bis zwei Klassen je Schulteil.
3. **Stammdatenkontrolle zum Schuljahresbeginn** über das EIB, mit Änderungsliste.
4. **Ausweitung:** Änderungsmeldung dauerhaft freischalten, weitere Papierformulare nach und nach umstellen.
5. **Bewertung:** Rücklaufquote, Anteil der Papier-Fallbacks, Zeitaufwand im Sekretariat vorher und nachher.

## 7. Vor- und Nachteile

### Vorteile

- **Weniger Aufwand im Sekretariat:** Es gibt kein Feld-für-Feld-Vergleichen mehr. Übertragen werden nur echte Änderungen, abhakbar und nachvollziehbar.
- **Klassenleitungen werden entlastet:** Austeilen, Einsammeln und Hinterherlaufen entfallen. Sie sehen nur noch, wer fehlt.
- **Keine unvollständigen Abgaben:** Pflichtfelder, Formatprüfung (Telefon, E-Mail, Datum) und die Bestätigung werden bei der Abgabe erzwungen.
- **Schnellere Rückfragen:** Eine Rückfrage geht direkt vom Sekretariat an die Eltern, ohne Umweg über die Klassenleitung.
- **Transparenz:** Der Rücklauf ist jederzeit sichtbar, Erinnerungen laufen automatisch.
- **Nachweisbarkeit:** Das Audit zeigt, wer wann was bestätigt hat, und liefert ein PDF für die Akte. Das ist oft besser nachweisbar als eine Papierunterschrift.
- **Weniger Papier und Druckkosten.**
- **Nutzt vorhandene Infrastruktur:** Konten, Kinder, Sorgerecht, Import, Benachrichtigungen, PDF und Audit gibt es schon. Es entsteht kein neues System und kein neuer Login.
- **Eine Lösung für das ganze Schulzentrum**, weil auch alle Oberschul-Eltern ein EIB-Konto haben.
- **Für Eltern bequemer:** „Alles korrekt“ ist ein Klick, jederzeit auf dem Handy.

### Nachteile / Risiken

- **Die Übertragung bleibt manuell:** Ohne Schnittstelle muss das Sekretariat Änderungen weiter abtippen, wenn auch deutlich weniger.
- **Doppelte Datenhaltung:** Das EIB hält Stammdaten (Snapshot oder Spiegel). Das bringt mehr Datenschutzpflichten, und die Daten können auseinanderlaufen (vor allem Variante B).
- **Abhängigkeit vom Export:** Ändert der Hersteller das Exportformat, muss das Spalten-Mapping angepasst werden.
- **Zwei Plattformen in der Oberschule:** Eltern müssen für Formulare ins EIB. Ohne gute Benachrichtigung sinkt der Rücklauf.
- **Grenzen der digitalen Unterschrift:** Formulare mit gesetzlicher Schriftform bleiben auf Papier bzw. als PDF zum Unterschreiben.
- **Digitale Teilhabe:** Einige Familien brauchen weiter Papier. Der Fallback ist eingeplant, verursacht aber Restaufwand.
- **Entwicklungs- und Pflegeaufwand** für ein neues Modul (siehe 5.4) und Schulung von Sekretariat und Kollegium.
- **Sensible Daten im EIB** (z. B. Gesundheitsdaten) erhöhen die Anforderungen an Hosting, Verschlüsselung und Zugriffsrechte.

## 8. Datenschutz & Recht (zu klären)

- Rechtsgrundlage je Formular: schulrechtliche Pflichtangaben (öffentliche Aufgabe) vs. freiwillige Einwilligungen.
- Verarbeitungsverzeichnis ergänzen. Falls das EIB extern gehostet wird: Auftragsverarbeitungsvertrag prüfen.
- Ggf. Datenschutz-Folgenabschätzung, wenn Gesundheitsdaten verarbeitet werden.
- Landesrechtliche Vorgaben zur Verarbeitung von Schülerdaten außerhalb der amtlichen Schulverwaltungssoftware prüfen (Schulgesetz/Schuldatenschutzverordnung des Landes, Vorgaben des Schulträgers).
- Datenminimierung: Snapshot nur mit den Feldern der jeweiligen Vorlage; Löschung nach Abschluss; Klassenleitung ohne Inhaltszugriff.
- Löschfristen für Abgaben (z. B. Einwilligungen bis Widerruf/Schulaustritt plus Nachweisfrist; Stammdatenmeldungen nach Übertragung).
- Widerruf von Einwilligungen jederzeit digital ermöglichen. Der Widerruf erscheint in der Änderungsliste.

## 9. Alternative Lösungen

| # | Alternative | Kurzbeschreibung | Vorteile | Nachteile |
|---|---|---|---|---|
| 1 | **Vorhandene Abfragen im EIB nutzen** (Quick Win) | Formulare als Nachricht mit Abfrage (Rückmeldung pro Kind, Pflichtfelder) | sofort verfügbar, kein Entwicklungsaufwand | keine Vorausfüllung, kein Diff, das Sekretariat vergleicht weiter alles; kaum Statusübersicht für Klassenleitungen |
| 2 | **Vorausgefüllte Papierbögen** (Serienbrief aus der Stammdaten-Software) | Die Software druckt pro Kind den aktuellen Datenstand; Eltern markieren nur Änderungen und unterschreiben | keine Software-Anpassung; stark verringerter Abgleichaufwand; sofort umsetzbar | Verteil- und Sammelkreislauf und Rückfragen bleiben; Papier |
| 3 | **Allgemeines Formular-Tool** (z. B. selbst gehostetes LimeSurvey, Nextcloud Forms) | Online-Formular per Link | schnell, flexibel | keine Verknüpfung mit Konten und Kindern → Identität unsicher, manuelle Zuordnung; weiteres System; Unterschriftsnachweis schwach; Cloud-Dienste (z. B. Microsoft/Google Forms) datenschutzrechtlich problematisch |
| 4 | **Formularfunktionen in SDUI** (für die Oberschule, falls verfügbar) | Umfragen/Formulare im Messenger | Oberschul-Eltern sind dort aktiv | zwei getrennte Lösungen im Schulzentrum; ebenfalls kein Stammdatenabgleich; laut Vorgabe nicht anpassbar |
| 5 | **Ausfüllbare PDF per Mail** | Eltern füllen PDF aus und senden es zurück | geringer Aufwand | Mail-Postfach wird zur Sammelstelle; keine Prüfung auf Vollständigkeit; kein Statusüberblick; Abgleich bleibt |
| 6 | **Hersteller/Schulträger:** Import- oder API-Funktion bzw. Eltern-Portal anfragen | Langfristig die eigentliche Ursache beheben | echte Automatisierung möglich | außerhalb des eigenen Einflusses; ungewiss und langsam |
| 7 | **UI-Automatisierung (RPA)** | Ein Skript überträgt die Änderungsliste per simulierter Tastatureingabe in die Software | spart auch das Abtippen | fehleranfällig bei Programm-Updates; schwer nachvollziehbar; datenschutz- und haftungsrechtlich heikel; bei Landessoftware ggf. unzulässig. **Nicht empfohlen** |
| 8 | **Scan + Texterkennung** | Papierbögen scannen und automatisch auswerten | Eltern-Prozess bleibt vertraut | Handschrift-Erkennung unzuverlässig; Verteilen/Sammeln bleibt; hoher Einrichtungsaufwand |
| 9 | **Reine Prozessänderung** | Stammdatenkontrolle nur noch alle 2 Jahre bzw. nur bei Schulwechsel; dazwischen Änderungsmeldung durch Eltern | kein Technikaufwand | Datenqualität sinkt; Rücklauf von Änderungsmeldungen unsicher |

**Empfehlung:**
- **Kurzfristig** Alternative 2 (vorausgefüllte Papierbögen), falls die Stammdaten-Software Serienbriefe kann. Das entlastet sofort und kostet nichts.
- **Parallel** Alternative 1 für einfache Einwilligungen und Kenntnisnahmen.
- **Mittelfristig** das Formular-Modul aus diesem Konzept umsetzen, bevorzugt mit Variante A (Export-Spiegel).
- Alternative 6 als langfristigen Wunsch beim Schulträger bzw. Hersteller platzieren.

## 10. Offene Fragen

1. Welche Exportformate und -felder bietet die Stammdaten-Software (Listen, Serienbrief, CSV)? Gibt es eine eindeutige Schüler-ID?
2. Welche Formulare gibt es heute konkret? Welche davon verlangen nachweislich Schriftform?
3. Genügt für Stammdaten die Bestätigung durch *einen* Sorgeberechtigten?
4. Sind alle Oberschüler bereits als Kinder mit Sorgeberechtigten im EIB erfasst?
5. Wer ist im Sekretariat bzw. in der Verwaltung zuständig? Sollen Klassenleitungen Inhalte sehen oder nur den Rücklauf?
6. Wo wird das EIB gehostet, und ist die Verarbeitung von Stammdaten (inkl. Gesundheitsdaten) dort zulässig?
