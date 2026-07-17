<p align="center"><img src="https://mitarbeiter.esz-radebeul.de/img/logo.png" width="400"></p>

<h1 align="center">ElternInfoBoard</h1>

<p align="center">
  <strong>Die digitale Kommunikationsplattform für Schulen</strong><br/>
  Basierend auf <a href="https://laravel.com/">Laravel 12</a> · <a href="https://laravel-livewire.com/">Livewire 3</a> · <a href="https://tailwindcss.com/">Tailwind CSS 3</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-%5E8.2-blue" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Laravel-12-red" alt="Laravel 12">
  <img src="https://img.shields.io/badge/License-AGPL--3.0-green" alt="AGPL-3.0">
</p>

---

## Über das ElternInfoBoard

Das **ElternInfoBoard** ist eine webbasierte Kommunikations- und Organisationsplattform, die ursprünglich für das Evangelische Schulzentrum Radebeul entwickelt wurde. Es vernetzt Eltern, Lehrkräfte und Schülerinnen und Schüler an einem zentralen Ort.

Zu den Kernfunktionen gehören:

- 📅 **Termine & Kalender** – Schulveranstaltungen, Elternabende und iCal-Export
- 📢 **Informations-Feed** – Posts, Kommentare und Reaktionen nach Gruppen/Klassen
- 💬 **Messenger** – Direktnachrichten mit Lesebestätigungen und Meldungen
- 📋 **Listen & Anmeldungen** – digitale Eintragungslisten mit Terminen
- 📊 **Umfragen & Abfragen** – interaktive Polls und strukturierte Abfragen
- 🔔 **Push-Benachrichtigungen** – Web-Push (VAPID) für alle wichtigen Ereignisse
- 📧 **E-Mail-Benachrichtigungen** – konfigurierbare Mailbenachrichtigungen
- 🤒 **Krankmeldungen** – digitale Krankmeldung mit Bestätigungsworkflow
- 🗓️ **Stundenplan** – Import und Darstellung des Stundenplans
- 👪 **Elternrat** – Aufgaben, Ereignisse und Verwaltung des Elternrats
- 🏫 **Arbeitsgemeinschaften** – Verwaltung von AGs und Mitgliedschaften
- 🧹 **Reinigungsdienste** – Zuweisung und Protokollierung
- 📨 **Rückmeldungen** – digitale Einverständniserklärungen und Formulare
- 🔑 **SSO via Keycloak / OIDC** – Single Sign-On für Eltern

---

## Lizenz & Nutzung

Das ElternInfoBoard steht unter der **GNU Affero General Public License v3.0 (AGPL-3.0)**.  
Es darf frei für nicht-kommerzielle Projekte im Bildungsbereich genutzt werden.

> ⚠️ Es besteht **kein Anspruch auf Support oder Haftung**. Änderungen und Weiterentwicklungen müssen ebenfalls als Open Source unter AGPL-3.0 veröffentlicht werden.

---

## Systemvoraussetzungen

| Komponente | Version |
|---|---|
| PHP | ≥ 8.2 (mit `ext-curl`) |
| Composer | ≥ 2.x |
| Node.js / npm | ≥ 18 / ≥ 9 |
| Datenbank | MySQL 8 / MariaDB 10.6+ |
| Queue-Worker | Supervisor o.Ä. empfohlen |

---

## Installation

### 1. Umgebungsdatei anlegen

```bash
cp .env.example .env
```

Die `.env`-Datei öffnen und mindestens folgende Felder ausfüllen:

```dotenv
APP_NAME="ElternInfoBoard"
APP_URL=https://deine-domain.de

DB_HOST=127.0.0.1
DB_DATABASE=elterninfo
DB_USERNAME=user
DB_PASSWORD=secret

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_FROM_ADDRESS=info@schule.de

ADMIN_EMAIL=admin@schule.de   # E-Mail des ersten Adminbenutzers
```

### 2. Abhängigkeiten installieren

```bash
composer install --no-dev --optimize-autoloader
npm install
```

### 3. Anwendung einrichten

```bash
php artisan key:generate
php artisan webpush:vapid
php artisan migrate
npm run build
```

> Beim ersten `migrate` wird automatisch ein Admin-Benutzer mit der in `.env` hinterlegten `ADMIN_EMAIL` angelegt. Das initiale Passwort ist das aktuelle Datum im Format `TTMMJJJJ` und **muss beim ersten Login geändert werden**.

### 4. Speicher-Symlink anlegen

```bash
php artisan storage:link
```

### 5. CronJob einrichten

```bash
crontab -e
```

Eintragen:

```cron
* * * * * cd /pfad/zum/projekt && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Queue-Worker starten

```bash
php artisan queue:work --sleep=3 --tries=3
```

Für den produktiven Betrieb wird Supervisor empfohlen.

---

## Entwicklungsumgebung starten

Alle Prozesse (Server, Queue, Logs, Vite) lassen sich mit einem Befehl starten:

```bash
composer run dev
```

Oder einzeln:

```bash
php artisan serve          # Webserver
php artisan queue:listen   # Queue-Worker
npm run dev                # Vite Asset-Compiler
php artisan pail           # Log-Viewer
```

---

## Tests ausführen

```bash
composer run test
```

Für parallele Tests:

```bash
php artisan test --parallel
```

---

## UCS@school-Integration

Das ElternInfoBoard kann Eltern und Klassen automatisch aus einer
[UCS@school](https://www.univention.de/produkte/ucsschool/)-Instanz
über die **Kelvin REST API** synchronisieren. Durch die Integration
entfällt die manuelle Pflege von Klassen-Zuordnungen; Eltern können sich
außerdem per OIDC/Keycloak einmalig anmelden.

### Voraussetzungen

- **Kelvin-Service-Account** in UCS angelegt (Leserechte auf Schüler- und Erziehungsberechtigtendaten)
- **OIDC-Client** in Keycloak (Eltern-Realm) konfiguriert
- **Netzwerk-Freigabe**: Der Server muss den Kelvin-API-Host über HTTPS erreichen können
- Queue-Worker läuft mit `--timeout=900`

### Einrichtung

1. Im Backend unter **Einstellungen → UCS@school** folgende Felder füllen:
   - `Kelvin Base URL` – z. B. `https://ucs.schule.example/ucsschool/kelvin/v1`
   - `Kelvin Benutzername` + `Passwort` – Service-Account-Credentials
   - `Schulname` – interner UCS-Schulkürzel
   - `Synchronisation aktivieren` – Schalter auf *An*
2. Unter **Einstellungen → OIDC / Keycloak** die OIDC-Parameter hinterlegen
   (Base URL, Realm, Client-ID/Secret, Redirect-URI, Maildomain)
3. Verbindung testen: `php artisan ucs:ping` oder Test-Button im Settings-Tab
4. Ersten Sync starten: `php artisan ucs:sync-parents --dry-run` (Probelauf) oder Sync-Button im UCS-Tab

### Automatischer Sync

Nach der Einrichtung läuft der Sync automatisch als geplanter Task.

```bash
php artisan schedule:list
```

---

## Theme-System

Das ElternInfoBoard unterstützt anpassbare Themes für verschiedene Schulen oder Mandanten.

---



## Tech-Stack

| Bereich | Technologie |
|---|---|
| Backend-Framework | Laravel 12 |
| Frontend-Reaktivität | Livewire 3 + Alpine.js 3 |
| CSS-Framework | Tailwind CSS 3 |
| Build-Tool | Vite 7 |
| Berechtigungen | Spatie Laravel Permission |
| Einstellungen | Spatie Laravel Settings |
| Medienverwaltung | Spatie Laravel Medialibrary |
| Authentifizierung | Laravel Sanctum + Socialite (Keycloak) |
| Push-Benachrichtigungen | laravel-notification-channels/webpush |
| Audit-Log | owen-it/laravel-auditing |
| PDF-Export | barryvdh/laravel-dompdf |
| Excel-Import/Export | Maatwebsite/Laravel-Excel |
| Datei-Storage | Lokal 
