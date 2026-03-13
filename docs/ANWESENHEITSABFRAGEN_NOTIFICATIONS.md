# Anwesenheitsabfragen Benachrichtigungssystem

## Übersicht

Dieses Feature implementiert ein Benachrichtigungssystem für Anwesenheitsabfragen im Care-Modul. Eltern werden automatisch über neue Anwesenheitsabfragen informiert und erhalten Erinnerungen, wenn eine Frist bald abläuft.

## Implementierte Funktionen

### 1. Benachrichtigung bei neuen Anwesenheitsabfragen

Wenn eine neue Anwesenheitsabfrage erstellt wird, werden die Eltern der betroffenen Kinder **einmalig** benachrichtigt, unabhängig davon, für wie viele Tage die Abfrage gilt.

**Funktionsweise:**
- Beim Erstellen einer Anwesenheitsabfrage über die Verwaltungsoberfläche werden alle betroffenen Eltern automatisch benachrichtigt
- Jeder Elternteil erhält nur **eine** Benachrichtigung pro Abfrage, auch wenn mehrere Tage abgefragt werden
- Die Benachrichtigung enthält:
  - Titel: "Neue Anwesenheitsabfrage"
  - Zeitraum der Abfrage (von-bis Datum)
  - Frist zur Rückmeldung (wenn gesetzt)
  - Link zur Schickzeiten-Seite

**Implementierung:**
- Datei: `app/Http/Controllers/Anwesenheit/CareController.php`
- Methode: `storeAbfrage()` und `notifyParentsAboutNewAttendanceQuery()`
- Notification-Klasse: `app/Notifications/AttendanceQueryNotification.php`

### 2. Erinnerung 3 Tage vor Ablauf der Frist

Das System sendet automatisch Erinnerungen an Eltern, deren Kinder Anwesenheitsabfragen haben, die in 3 Tagen ablaufen und noch nicht beantwortet wurden.

**Funktionsweise:**
- Täglich um 08:00 Uhr wird automatisch geprüft, welche Abfragen in genau 3 Tagen ablaufen
- Nur Eltern mit **unbeantworteten** Abfragen (should_be = null) werden benachrichtigt
- Jeder Elternteil erhält nur **eine** Erinnerung pro Ablaufdatum, auch wenn mehrere Kinder betroffen sind
- Die Erinnerung enthält:
  - Titel: "Erinnerung: Anwesenheitsabfrage läuft ab"
  - Ablaufdatum
  - Namen der betroffenen Kinder
  - Aufforderung zur Rückmeldung
  - Link zur Schickzeiten-Seite

**Implementierung:**
- Job: `app/Jobs/SendAttendanceQueryReminderJob.php`
- Scheduler: `routes/console.php` (täglich um 08:00 Uhr)
- Command: `app/Console/Commands/SendAttendanceQueryReminders.php` (für manuellen Test)

### 3. Benachrichtigungsarten

Das System verwendet zwei Arten von Benachrichtigungen:

1. **Datenbank-Benachrichtigungen** (`Notification` Model)
   - Werden in der Datenbank gespeichert
   - Können in der Benutzeroberfläche angezeigt werden
   - Typ: "Anwesenheitsabfrage"

2. **Push-Benachrichtigungen** (WebPush)
   - Werden direkt an das Gerät des Benutzers gesendet
   - Erscheinen als Browser-Benachrichtigung
   - Nutzen das bestehende WebPush-System

## Technische Details

### Neue Dateien

1. **app/Notifications/AttendanceQueryNotification.php**
   - Notification-Klasse für Anwesenheitsabfragen
   - Unterstützt WebPush und Database-Channel
   - Enthält Titel, Nachricht und URL

2. **app/Jobs/SendAttendanceQueryReminderJob.php**
   - Job für automatische Erinnerungen
   - Wird vom Scheduler ausgeführt
   - Gruppiert Benachrichtigungen nach Eltern und Datum

3. **app/Console/Commands/SendAttendanceQueryReminders.php**
   - Artisan-Command für manuelle Tests
   - Führt den Reminder-Job aus

### Geänderte Dateien

1. **app/Http/Controllers/Anwesenheit/CareController.php**
   - Import der `AttendanceQueryNotification`
   - Anpassung der `storeAbfrage()` Methode
   - Neue Methode `notifyParentsAboutNewAttendanceQuery()`
   - Prüfung auf bestehende CheckIns, um Duplikate zu vermeiden
   - `should_be` wird auf `null` gesetzt (statt `false`) für unbeantwortete Abfragen

2. **app/Http/Controllers/SchickzeitenController.php**
   - Import der Notification-Klassen
   - Erweiterte `storeAbfrageAnwesenheit()` Methode
   - Neue Methode `notifyParentsAboutNewAttendanceQuery()`

3. **routes/console.php**
   - Scheduler-Eintrag für tägliche Erinnerungen um 08:00 Uhr

## Verwendung

### Für Administratoren

**Neue Anwesenheitsabfrage erstellen:**
1. Navigieren Sie zur Schickzeiten-Verwaltung
2. Tab "Anwesenheitsabfragen" öffnen
3. Formular "Neue Anwesenheitsabfrage erstellen" ausfüllen:
   - Datum von (Pflichtfeld)
   - Datum bis (optional)
   - Frist zur Rückmeldung (optional, wichtig für Erinnerungen!)
4. Beim Absenden werden alle betroffenen Eltern automatisch benachrichtigt

**Wichtig:** 
- Das Feld "Bis wann ist eine Anmeldung möglich" (lock_at) sollte gesetzt werden, damit Erinnerungen 3 Tage vorher versendet werden können
- Ohne gesetztes lock_at werden keine Erinnerungen versendet

### Für Eltern

**Benachrichtigungen erhalten:**
- Bei neuen Anwesenheitsabfragen erscheint eine Benachrichtigung
- 3 Tage vor Ablauf der Frist wird eine Erinnerung gesendet (falls noch nicht beantwortet)
- Benachrichtigungen können in der App und als Push-Nachricht empfangen werden

**Auf Abfragen antworten:**
1. Klicken Sie auf die Benachrichtigung oder navigieren Sie zu "Schickzeiten"
2. Tab "Anwesenheitsabfrage" öffnen
3. Für jeden Tag die Anwesenheit bestätigen oder ablehnen

## Testen

### Manueller Test der Erinnerungen

```bash
php artisan attendance:send-reminders
```

Dieser Befehl sendet Erinnerungen für alle Anwesenheitsabfragen, die in genau 3 Tagen ablaufen.

### Test-Szenario

1. Erstellen Sie eine Anwesenheitsabfrage mit:
   - Datum: In 1 Woche
   - Frist (lock_at): In 4 Tagen
   
2. Prüfen Sie:
   - Eltern erhalten sofort eine Benachrichtigung über die neue Abfrage
   
3. Warten Sie 1 Tag (oder setzen Sie das Datum manuell)

4. Führen Sie aus: `php artisan attendance:send-reminders`

5. Prüfen Sie:
   - Eltern erhalten eine Erinnerung, da die Frist in 3 Tagen abläuft

6. Lassen Sie einen Elternteil antworten (should_be auf true/false setzen)

7. Führen Sie erneut aus: `php artisan attendance:send-reminders`

8. Prüfen Sie:
   - Der Elternteil, der geantwortet hat, erhält keine Erinnerung mehr
   - Andere Eltern (die nicht geantwortet haben) erhalten weiterhin Erinnerungen

## Scheduler

Die Erinnerungen werden automatisch vom Laravel Scheduler versendet:

```php
Schedule::job(new \App\Jobs\SendAttendanceQueryReminderJob)->dailyAt('08:00');
```

**Wichtig:** Der Scheduler muss auf dem Server aktiv sein (Cron-Job oder Supervisor).

## Datenbank-Änderungen

Keine neuen Tabellen oder Spalten erforderlich. Das System nutzt:
- Bestehende `child_check_ins` Tabelle mit `lock_at` und `should_be` Spalten
- Bestehende `notifications` Tabelle
- Bestehende WebPush-Infrastruktur

## Logging

Das System loggt folgende Ereignisse:

- **Info**: Erfolgreich versendete Benachrichtigungen
  - `"Neue Anwesenheitsabfrage-Benachrichtigung an {Name} (ID: {ID}) gesendet"`
  - `"Anwesenheitsabfragen-Erinnerung an {Name} (ID: {ID}) gesendet"`
  
- **Info**: Zusammenfassung der versendeten Erinnerungen
  - `"Sende Anwesenheitsabfragen-Erinnerungen für Datum: {Datum}"`
  - `"Anwesenheitsabfragen-Erinnerungen versendet: {Anzahl} Benachrichtigungen"`
  
- **Error**: Fehler beim Versenden von Benachrichtigungen
  - `"Fehler beim Senden der Anwesenheitsabfrage-Benachrichtigung an {Name}: {Fehlermeldung}"`

## Zukünftige Erweiterungen

Mögliche Erweiterungen:
- Konfigurierbare Erinnerungs-Zeitpunkte (nicht nur 3 Tage vorher)
- Mehrere Erinnerungen (z.B. 7 Tage, 3 Tage, 1 Tag vorher)
- E-Mail-Benachrichtigungen zusätzlich zu Push-Notifications
- Benachrichtigung für Administratoren bei geringer Rücklaufquote
- Automatische Eskalation bei fehlenden Rückmeldungen

