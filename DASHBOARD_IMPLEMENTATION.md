# Dashboard-Implementierung

## Änderungen

Die Anwendung wurde von einer vollständigen Anzeige aller Inhalte auf der Startseite auf ein Dashboard-System umgestellt.

## Neue Struktur

### Dashboard (Startseite `/`)
- Zeigt nur die wichtigsten Informationen als Übersicht
- **Losung des Tages** (wenn aktiviert)
- **Neueste 5 Nachrichten** als Teaser mit "Weiterlesen"-Link
- **Nächste 5 Termine** in kompakter Darstellung
- **Schnellzugriff-Karten** zu wichtigen Bereichen (Schickzeiten, Krankmeldung, Kinder, Listen)

### Detailseiten

#### Nachrichten-Übersicht (`/nachrichten`)
- Zeigt alle Nachrichten vollständig
- Inhaltsverzeichnis mit Anker-Links
- Filter nach Gruppen
- Alle bisherigen Funktionen (Rückmeldungen, Reaktionen, etc.)

#### Termine-Übersicht (`/termin`)
- Zeigt alle zukünftigen Termine
- Gruppiert nach Monaten
- Vollständige Termindetails (Datum, Uhrzeit, Gruppen)
- Bearbeitungsmöglichkeit für berechtigte Nutzer

## Neue Dateien

1. **DashboardController.php** (`app/Http/Controllers/DashboardController.php`)
   - Controller für die Dashboard-Ansicht
   - Lädt die neuesten 5 Nachrichten und nächsten 5 Termine

2. **dashboard/index.blade.php** (`resources/views/dashboard/index.blade.php`)
   - Dashboard-View mit Übersicht

3. **nachrichten/index.blade.php** (`resources/views/nachrichten/index.blade.php`)
   - Vollständige Nachrichtenübersicht

4. **termine/index.blade.php** (`resources/views/termine/index.blade.php`)
   - Vollständige Terminübersicht

## Geänderte Dateien

1. **routes/web.php**
   - `/` Route zeigt jetzt Dashboard statt Nachrichten
   - Neue Route `/nachrichten` für alle Nachrichten
   - Termine nutzen die bestehende Resource-Route

2. **NachrichtenController.php**
   - `index()` Methode lädt jetzt alle Nachrichten für die Übersichtsseite

3. **TerminController.php**
   - Neue `index()` Methode für die Terminübersicht

## Modul-Konfiguration

**WICHTIG**: Die Module "Nachrichten" und "Termine" sollten in den Einstellungen so angepasst werden, dass sie nicht mehr `home-view` oder `home-view-top` verwenden, damit das Dashboard nicht mit den alten Views überlagert wird.

### Empfohlene Modul-Anpassungen:

**Nachrichten-Modul:**
```json
{
  "active": "1",
  "rights": [],
  "nav": {
    "name": "Nachrichten",
    "link": "nachrichten",
    "icon": "far fa-newspaper",
    "bottom-nav": "true"
  },
  "adm-nav": {
    "adm-rights": ["create posts"],
    "name": "neue Nachricht",
    "link": "posts/create",
    "icon": "fas fa-pen"
  }
}
```

**Termine-Modul:**
```json
{
  "active": "1",
  "rights": [],
  "nav": {
    "name": "Termine",
    "link": "termin",
    "icon": "far fa-calendar-alt",
    "bottom-nav": "true"
  },
  "adm-nav": {
    "adm-rights": ["edit termin"],
    "name": "neuer Termin",
    "link": "termin/create",
    "icon": "far fa-calendar-alt"
  }
}
```

## Navigation

Die Navigationselemente in der Sidebar verlinken jetzt:
- **Dashboard**: `/` (Startseite)
- **Nachrichten**: `/nachrichten` (Alle Nachrichten)
- **Termine**: `/termin` (Alle Termine)

## Vorteile

1. **Übersichtlichkeit**: Startseite zeigt nur die wichtigsten Informationen
2. **Performance**: Weniger Daten müssen initial geladen werden
3. **Bessere UX**: Nutzer erhalten schneller einen Überblick
4. **Flexibilität**: Schnellzugriff-Bereiche können leicht erweitert werden

