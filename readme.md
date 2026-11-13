<p align="center"><img src="https://mitarbeiter.esz-radebeul.de/img/logo.png" width="400"></p>

<h1 align="center">ElternInfoBoard</h1>

<p align="center">
  <strong>Die digitale Kommunikationsplattform für Schulen</strong><br/>
  Basierend auf <a href="https://laravel.com/">Laravel 12</a> · <a href="https://livewire.laravel.com/">Livewire 3</a> · <a href="https://tailwindcss.com/">Tailwind CSS 3</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4-blue" alt="PHP 8.4">
  <img src="https://img.shields.io/badge/Laravel-12-red" alt="Laravel 12">
  <img src="https://img.shields.io/badge/License-AGPL--3.0-green" alt="AGPL-3.0">
</p>

---

## Inhalt

- [Über das ElternInfoBoard](#über-das-elterninfoboard)
- [Lizenz & Nutzung](#lizenz--nutzung)
- [Systemvoraussetzungen](#systemvoraussetzungen)
- [Installation](#installation)
- [Konfiguration (`.env`)](#konfiguration-env)
- [Einstellungen in der Oberfläche](#einstellungen-in-der-oberfläche)
- [Betrieb](#betrieb)
- [Sicherheitsprotokoll und Datenschutz](#sicherheitsprotokoll-und-datenschutz)
- [Updates](#updates)
- [UCS@school-Integration](#ucsschool-integration)
- [Eltern-App & MCP-Server](#eltern-app--mcp-server)
- [Hilfreiche Artisan-Befehle](#hilfreiche-artisan-befehle)
- [Entwicklung & Tests](#entwicklung--tests)
- [Theme-System](#theme-system)
- [Tech-Stack](#tech-stack)

---

## Über das ElternInfoBoard

Das **ElternInfoBoard** ist eine webbasierte Kommunikations- und Organisationsplattform, die ursprünglich für das Evangelische Schulzentrum Radebeul entwickelt wurde. Es vernetzt Eltern, Lehrkräfte und Schülerinnen und Schüler an einem zentralen Ort.

Zu den Kernfunktionen gehören:

- 📅 **Termine & Kalender** – Schulveranstaltungen, Elternabende und iCal-Export
- 📢 **Informations-Feed** – Beiträge, Kommentare und Reaktionen nach Gruppen/Klassen
- 💬 **Messenger** – Direktnachrichten mit Lesebestätigungen und Meldungen
- 📋 **Listen & Anmeldungen** – digitale Eintragungslisten mit Terminen
- 📊 **Umfragen & Elternabfragen** – Umfragen und strukturierte Abfragen mit Fristen
- 🔔 **Push-Benachrichtigungen** – Web-Push (VAPID) und native App-Mitteilungen
- 📧 **E-Mail-Benachrichtigungen** – tägliche/wöchentliche Zusammenfassungen
- 🤒 **Krankmeldungen** – digitale Krankmeldung mit Tagesbericht
- 🧒 **Anwesenheit & Schickzeiten** – Check-in der Hortkinder, Abholzeiten
- 🗓️ **Stundenplan** – Import und Darstellung des Stundenplans
- 👪 **Elternrat** – Aufgaben, Ereignisse und Dateiablage des Elternrats
- 🏫 **Arbeitsgemeinschaften** – Verwaltung von AGs und Mitgliedschaften
- ⏱️ **Pflichtstunden** – Erfassung und Jahresabschluss der Elternstunden
- 🧹 **Reinigungsdienste** – Zuweisung, Erinnerung und Protokollierung
- 📨 **Rückmeldungen** – digitale Einverständniserklärungen und Formulare
- 🔑 **SSO via Keycloak / OIDC** – Single Sign-On für Eltern
- 🔄 **UCS@school-Anbindung** – automatischer Abgleich von Eltern und Klassen
- 📱 **Eltern-App** – eigene App (React Native/Expo) über die App-API v1

---

## Lizenz & Nutzung

Das ElternInfoBoard steht unter der **GNU Affero General Public License v3.0 (AGPL-3.0)**.
Es darf frei für nicht-kommerzielle Projekte im Bildungsbereich genutzt werden.

> ⚠️ Es besteht **kein Anspruch auf Support oder Haftung**. Änderungen und Weiterentwicklungen müssen ebenfalls als Open Source unter AGPL-3.0 veröffentlicht werden.

---

## Systemvoraussetzungen

| Komponente | Version / Hinweis |
|---|---|
| PHP | **8.4** (die `composer.lock` ist gegen PHP 8.4 aufgelöst) |
| PHP-Erweiterungen | `curl`, `gd`, `mbstring`, `openssl`, `pdo_mysql`, `xml`, `zip`, `intl`, `bcmath`; empfohlen `imagick` |
| Composer | ≥ 2.x |
| Node.js / npm | Node ≥ 20.19 bzw. ≥ 22.12 (Vite 7) – nur zum Bauen der Assets |
| Datenbank | MySQL 8 / MariaDB 10.6+ |
| Webserver | Apache (mit `mod_rewrite`) oder nginx, Document-Root auf `public/` |
| Cron | ein Cronjob für `php artisan schedule:run` (Pflicht) |
| Queue-Worker | Supervisor/systemd empfohlen – alternativ über den Cronjob (`USE_CRONJOB=true`) |
| Optional | `git` und `mysqldump` für das Online-Update |

---

## Installation

> Für eine schnelle Erstinstallation gibt es das interaktive Skript `install.sh`. Es fragt URL, Datenbank- und Mailzugang ab und erledigt die Schritte 2–4. Den Administrator (Schritt 5) und den Cronjob (Schritt 7) legt es **nicht** an.

### 1. Code holen

```bash
git clone https://github.com/Dreknor/ElternInfoBoard.git elterninfo
cd elterninfo
```

Den Webserver so einrichten, dass das Document-Root auf `public/` zeigt. Die Verzeichnisse `storage/` und `bootstrap/cache/` müssen für den Webserver-Benutzer beschreibbar sein.

### 2. Umgebungsdatei anlegen

```bash
cp .env.example .env
```

Mindestens diese Werte anpassen (alle weiteren Optionen siehe [Konfiguration](#konfiguration-env)):

```dotenv
APP_NAME="ElternInfoBoard"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://eltern.schule.de
APP_LOCALE=de
APP_FALLBACK_LOCALE=de
APP_FAKER_LOCALE=de_DE

DB_HOST=127.0.0.1
DB_DATABASE=elterninfo
DB_USERNAME=user
DB_PASSWORD=secret

MAIL_MAILER=smtp
MAIL_HOST=smtp.schule.de
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=info@schule.de
MAIL_FROM_NAME="ElternInfoBoard"
```

### 3. Abhängigkeiten installieren und Assets bauen

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

`public/build` ist nicht versioniert. Steht auf dem Server kein Node.js zur Verfügung, die Assets lokal bauen und `public/build` hochladen.

### 4. Anwendung einrichten

```bash
php artisan key:generate
php artisan webpush:vapid          # VAPID-Schlüssel für Web-Push in die .env schreiben
php artisan migrate --force        # Tabellen und Einstellungen (database/settings) anlegen
php artisan db:seed --force        # Rollen, Rechte, Module, Grundeinstellungen
php artisan storage:link
```

> Über den Symlink sind nur Logo und Favicon öffentlich. Hochgeladene Dateien (Beiträge, Messenger, Krankmeldungen …) liegen auf der privaten Disk `media` (`storage/app/media`) und werden ausschließlich mit Rechteprüfung über `/image/{id}` ausgeliefert. `MEDIA_DISK` **nicht** auf `public` setzen.

### 5. Ersten Administrator anlegen

```bash
php artisan app:create-first-admin
```

Der Befehl fragt Name, E-Mail und Passwort ab (oder `--name=… --email=… --password=…`). Bleibt das Passwort leer, wird ein sicheres Passwort erzeugt und angezeigt – es muss dann beim ersten Login geändert werden. Der Befehl bricht ab, wenn bereits ein Administrator existiert.

### 6. Caches für den Produktivbetrieb (optional)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Nach jeder Änderung an der `.env` muss `php artisan config:clear` (bzw. erneut `config:cache`) ausgeführt werden.

### 7. Cronjob einrichten (Pflicht)

Als Benutzer, dem die Dateien gehören (nicht `root`):

```cron
* * * * * cd /pfad/zum/projekt && php artisan schedule:run >> /dev/null 2>&1
```

Ohne Cronjob werden keine Mails versendet, keine Erinnerungen verschickt, keine Daten bereinigt und keine Online-Updates ausgeführt. Siehe [Geplante Aufgaben](#geplante-aufgaben).

### 8. Queue-Worker

E-Mails, Push-Mitteilungen und Erinnerungen laufen über die Queue (`QUEUE_CONNECTION=database`). Zwei Varianten:

**a) Supervisor (empfohlen)** – in der `.env` `USE_CRONJOB=false` setzen und z. B. `/etc/supervisor/conf.d/elterninfo-worker.conf` anlegen:

```ini
[program:elterninfo-worker]
command=php /pfad/zum/projekt/artisan queue:work --sleep=3 --tries=3 --timeout=900
user=www-data
numprocs=1
autostart=true
autorestart=true
stopwaitsecs=910
redirect_stderr=true
stdout_logfile=/pfad/zum/projekt/storage/logs/worker.log
```

Das `--timeout=900` ist für den UCS-Abgleich nötig.

**b) Ohne Supervisor (Shared-Hosting)** – `USE_CRONJOB=true` (Standard in der `.env.example`). Der Scheduler startet dann jede Minute `queue:work --stop-when-empty`. Speichergrenze über `QUEUE_WORKER_MEMORY` (MB, Standard 256).

### Demo-Instanz

Für eine Vorführ- oder Testinstanz mit Beispieldaten:

```bash
php artisan demo:setup            # fragt Admin-Zugang und Umgang mit vorhandenen Daten ab
php artisan demo:setup --fresh    # Datenbank vorher leeren (migrate:fresh --seed)
```

---

## Konfiguration (`.env`)

Grundsätzlich gilt: **Zugangsdaten und Server-Pfade** stehen in der `.env`, **fachliche Einstellungen** (Mailzeiten, Module, Design, SSO, UCS …) werden in der Oberfläche unter **Einstellungen** gepflegt und in der Datenbank gespeichert.

### Basis

| Variable | Bedeutung |
|---|---|
| `APP_ENV` / `APP_DEBUG` | Produktiv: `production` / `false` |
| `APP_URL` | Öffentliche URL ohne abschließenden Slash – wird für Links in Mails, Push und SSO-Redirect verwendet |
| `APP_LOCALE` | `de` empfohlen |
| `LOG_CHANNEL` | `daily` (Standard), Logs in `storage/logs` |
| `SENTRY_LARAVEL_DSN` | Optional: Fehlerberichte an Sentry |
| `CACHE_STORE` / `SESSION_DRIVER` | `file` (Standard) oder `redis` |
| `QUEUE_CONNECTION` | `database` (Standard) |
| `USE_CRONJOB` / `QUEUE_WORKER_MEMORY` | Queue über den Scheduler abarbeiten (siehe oben) |
| `MEDIA_DISK` | Standard `media` (privat) – nicht ändern |

### Darstellung

| Variable | Bedeutung |
|---|---|
| `APP_LOGO`, `APP_LOGO_SMALL`, `APP_FAVICON` | Dateinamen in `public/img` (Standard `logo.png`, `app_logo.png`, `favicon.ico`). Logo und Favicon lassen sich alternativ unter *Einstellungen → Allgemein* hochladen. |
| `ELTERNRAT_DIRS` | Ordner der Elternrat-Dateiablage, kommagetrennt |
| `SCHICKEN_AB` / `SCHICKEN_MAX` | Frühester / spätester Abholzeitpunkt (Standard 14:00 / 16:30) |
| `ENABLE_REACTIONS` | Reaktionen auf Beiträge (Standard `true`) |

### E-Mail und Push

| Variable | Bedeutung |
|---|---|
| `MAIL_*` | SMTP-Zugang. Kann unter *Einstellungen → E-Mail / SMTP* überschrieben werden. Testen mit `php artisan mail:test adresse@schule.de` |
| `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` | Web-Push, erzeugt mit `php artisan webpush:vapid`. **Nicht neu erzeugen**, sonst verlieren alle Abonnements ihre Gültigkeit. |

### Module und Erweiterungen

| Variable | Bedeutung |
|---|---|
| `MODULES_UNLOCKED` | Schaltet eingeschränkte Module frei (z. B. `"Schülerkonten"`), siehe `config/modules.php`. Nicht freigeschaltete Module erscheinen nicht in der Modulverwaltung. |
| `FAMILY_RESOLVER` / `FAMILY_DUAL_WRITE_SORG2` | Familienmodell: `legacy` (bisherige Kontoverknüpfung) oder das kind-zentrierte Modell, siehe `docs/kind-zentriertes-familienmodell-konzept.md`. Stand prüfen mit `php artisan family:status`. |
| `CUSTOM_SCRIPTS_ENABLED` | Erlaubt eigene Snippets (z. B. Chatbot) unter *Einstellungen → Eigene Skripte* |
| `WP_URL`, `WP_USER_NAME`, `WP_PASSWORD`, `WP_CATEGORIES`, `WP_PUSH_FILES` | Beiträge auf eine WordPress-Seite veröffentlichen (Anwendungspasswort in WordPress anlegen) |
| `API_KEY`, `LINK_MITARBEITERBOARD`, `API_KEY_MITARBEITERBOARD` | Anbindung an das MitarbeiterBoard (Vertretungsplan) |
| `PW_IMPORT_ELTERN`, `PW_IMPORT_AUFNAHME`, `PW_IMPORT_MITARBEITER`, `PW_IMPORT_VEREIN` | Startpasswörter beim Benutzerimport (Standard: Importdatum `TTMMJJJJ`) |
| `FREESCOUT_URL`, `FREESCOUT_API_KEY`, `FREESCOUT_MAILBOX_ID`, `FREESCOUT_QUEUE_SYNC`, `FREESCOUT_QUEUE_NAME` | Support-Anfragen an ein FreeScout-Ticketsystem weiterleiten |
| `KEYCLOAK_*` | Vorbelegung für SSO. Werte aus *Einstellungen → OIDC / Keycloak* haben Vorrang. |
| `UCS_*` | Startwerte für UCS@school, nur beim ersten `migrate` übernommen, siehe [unten](#ucsschool-integration) |
| `APNS_*`, `FCM_CREDENTIALS`, `APP_*` | Eltern-App, siehe [unten](#eltern-app--mcp-server) |
| `MCP_ENABLED`, `MCP_TOKEN_LIFETIME`, `MCP_DISABLED_TOOLS` | MCP-Server für Chatbots, siehe `docs/mcp-server.md` |
| `UPDATER_*` | Online-Update, siehe [Updates](#updates) |

---

## Einstellungen in der Oberfläche

Nach dem ersten Login als Administrator unter **Einstellungen** (Recht `edit settings`) mindestens prüfen:

| Bereich | Was einstellen |
|---|---|
| **Module** (`/settings/modules`) | Benötigte Module aktivieren, nicht genutzte deaktivieren. Davon hängt auch ab, welche geplanten Aufgaben laufen (z. B. Krankmeldungs-Tagesbericht, Anwesenheits-Check-in, Messenger-Bereinigung). |
| **Allgemein** | Name der Anwendung, Logo/Favicon, Standard-Theme und Navigation, geschützte Rollen, öffentliche Termine |
| **E-Mail / SMTP** | SMTP-Zugang (überschreibt die `MAIL_*`-Werte der `.env`, Passwort verschlüsselt gespeichert), Willkommenstexte, Kontaktadresse, Protokollierung gesendeter Mails |
| **Benachrichtigungen** | Wochentag und Uhrzeit der Informationsmail, Uhrzeit des Krankmeldungsberichts, Schickzeiten-Erinnerung |
| **Erinnerungen** | Uhrzeit der täglichen Erinnerungen (Rückmeldungen, Lesebestätigungen, Abfragen) |
| **Schuljahreswechsel** | Gruppen- und Rollenzuordnung zum neuen Schuljahr |
| **Care / Schickzeiten** | Check-in-Zeiten Schulzeit/Ferien, Abholregeln |
| **Pflichtstunden, Reinigungsplan, Stundenplan, Eltern-Nachrichten** | Je nach genutzten Modulen |
| **Design** | Farben und Theme |
| **OIDC / Keycloak** | SSO für Eltern (siehe unten) |
| **UCS@school** | Anbindung an Kelvin (siehe unten) |
| **Eigene Skripte** | Eingebundene Snippets (nur mit `CUSTOM_SCRIPTS_ENABLED=true`) |
| **Online-Update** | Updates prüfen und einspielen |

Weitere Punkte nach der Einrichtung:

- **Rollen & Rechte** unter `/roles` (Recht `edit permission`) an die Schule anpassen.
- **Gruppen/Klassen** anlegen bzw. über UCS@school abgleichen lassen.
- **Benutzer** importieren (Excel-Import) oder per SSO anlegen lassen.

> Die Zeitpläne des Schedulers werden aus den Einstellungen gelesen. Nach einer Änderung von Uhrzeiten mit `php artisan schedule:list` prüfen, ob sie übernommen wurden.

---

## Betrieb

### Geplante Aufgaben

Alle wiederkehrenden Aufgaben sind in `routes/console.php` definiert und laufen über den Cronjob. Überblick:

```bash
php artisan schedule:list
```

Dazu gehören unter anderem:

- Informationsmails (täglich bzw. wöchentlich zu der eingestellten Zeit)
- Erinnerungen für Rückmeldungen, Lesebestätigungen, Abfragen, Reinigung und Elternrat-Termine
- Krankmeldungs-Tagesbericht, Schickzeiten-Erinnerung, automatischer Check-in
- Elternabfragen (alle 15 Minuten: Starts, Erinnerungen, Export- und Löschfristen; wöchentlich Prüfung der Erklärungsquittungen, nach dem Update stündlich Nacherfassung fehlender Quittungen, bis keine mehr fehlen)
- UCS@school-Abgleich (nachts) und Bereinigung verwaister Klassen (sonntags)
- Online-Update, wenn in der Oberfläche angefordert
- **Datenschutz-Bereinigung**: Logs (90 Tage), Audit-Trail, Lesebestätigungen und Reminder-Logs (12 Monate), Check-ins und Kinder-Notizen (3 Monate), Krankmeldungen, Messenger-Nachrichten zum Schuljahresende (1. 8.), endgültige Löschung deaktivierter Benutzer nach 400 Tagen
- Monatlicher Inaktivitätsbericht an die Administratoren

### Datensicherung

Regelmäßig sichern:

- die **Datenbank** (z. B. `mysqldump`),
- `storage/app` (enthält u. a. die privaten Medien in `storage/app/media`, Updater-Sicherungen),
- die `.env` (insbesondere `APP_KEY` und die VAPID-Schlüssel – ohne `APP_KEY` sind verschlüsselte Daten nicht mehr lesbar).

### Protokolle und Fehlersuche

- Laravel-Logs: `storage/logs/laravel-*.log`
- Protokolle in der Oberfläche: Modul *Logs* (Recht erforderlich)
- Fehlgeschlagene Jobs: `php artisan queue:failed`, erneut ausführen mit `php artisan queue:retry all`
- Mailversand testen: `php artisan mail:test adresse@schule.de`
- App-Push testen: `php artisan push:check [email]`

---

## Sicherheitsprotokoll und Datenschutz

Die Anwendung führt ein eigenes, datensparsames **Sicherheitsprotokoll** (Tabelle `security_events`). Es ist vom allgemeinen System-Log (*Logs*) getrennt und dient ausschließlich dazu, unbefugte Zugriffe zu erkennen und nachzuweisen.

**Erfasste Ereignisse** (Punktnotation, in der Oberfläche mit deutscher Kurzbezeichnung): Anmeldung, fehlgeschlagene Anmeldung, Sperre nach zu vielen Versuchen, Abmeldung, Passwort-Reset und Anmeldelinks (`auth.*`), Ausstellen/Widerrufen/Erneuern von API-Tokens (`token.*`), Anmeldung als anderer Nutzer (`impersonation.*`), Rollen- und Rechteänderungen (`rbac.*`), Passwortänderungen (`account.*`), Löschen von System-Logs (`logs.deleted`), Exporte und Downloads (`export.download`, `media.download`, `inquiry.file_download`), MCP-Werkzeugaufrufe (`mcp.tool_call`), Abruf des Kalender-Feeds (`ical.fetch`), Download der Selbstauskunft (`privacy.export`) sowie die Einsichtnahme in das Protokoll selbst (`security_log.viewed`, höchstens einmal pro Nutzer und Stunde).

**Gespeicherte Daten:** Zeitpunkt, Ereignis, Konto-ID (und ggf. ID der handelnden Person), IP-Adresse, User-Agent und wenige technische Kennzeichen. Es werden **keine Inhalte, Passwörter, Tokens oder Codes** gespeichert; Kennungen wie E-Mail-Adressen erscheinen höchstens als gekürzter Hash.

**Zweck und Rechtsgrundlage (Hinweis, keine Rechtsberatung):** Gewährleistung der Sicherheit der Verarbeitung und Aufklärung von Sicherheitsvorfällen, Art. 6 Abs. 1 lit. f i. V. m. Art. 32 DSGVO. Für kirchliche Träger kommen § 6 Nr. 4 bzw. Nr. 8 i. V. m. § 27 DSG-EKD in Betracht. Eine Auswertung zur Leistungs- oder Verhaltenskontrolle ist nicht vorgesehen und darf nicht erfolgen. Die rechtliche Einordnung ist durch den Datenschutzbeauftragten des Trägers zu bestätigen.

**Aufbewahrung:** Einträge werden nach 90 Tagen gelöscht, die IP-Adresse wird nach 30 Tagen gekürzt und der User-Agent entfernt (täglich per `security-log:cleanup`, siehe `schedule:list`). Das Protokoll hat bewusst keinen Fremdschlüssel auf Benutzer: Es bleibt auch nach dem Löschen eines Kontos bis zum Fristablauf erhalten (die Ansicht zeigt dann nur die ID).

| Variable | Bedeutung |
|---|---|
| `SECURITY_LOG_ENABLED` | Protokollierung ein-/ausschalten (Standard `true`) |
| `SECURITY_LOG_RETENTION_DAYS` | Aufbewahrung in Tagen (Standard `90`) |
| `SECURITY_LOG_ANONYMIZE_IP_AFTER_DAYS` | IP kürzen / User-Agent entfernen nach Tagen (Standard `30`) |

**Zugriff:** Nur Nutzer mit dem Recht `see security log` (standardmäßig die Rolle *admin*) sehen unter *Verwaltung → Sicherheitsprotokoll* (`/security-log`) eine **reine Leseansicht** mit Filtern. Das Protokoll lässt sich über die Oberfläche weder löschen noch exportieren; jeder Aufruf wird selbst protokolliert. Das Recht sollte nur an wenige, namentlich bekannte Personen vergeben werden.

**Auskunftsrecht:** Die eigenen Einträge (Zeit, Ereignis, IP, User-Agent, Kontext; bei Auslösung durch eine andere Person nur „ja/nein“) sind Teil der Selbstauskunft unter *Datenschutz* (JSON und PDF). Der Download der Selbstauskunft wird als `privacy.export` protokolliert. Die Datenschutzseite für Nutzer weist auf das Sicherheitsprotokoll hin.

**Checkliste für den Betreiber (organisatorisch):**

- [ ] Verzeichnis der Verarbeitungstätigkeiten und Datenschutzerklärung um das Sicherheitsprotokoll ergänzen (Zweck, Daten, Fristen, Zugriffsberechtigte).
- [ ] Mitarbeitervertretung beteiligen: Das Protokoll erfasst auch Beschäftigte; eine Leistungs- und Verhaltenskontrolle ist auszuschließen (z. B. per Dienstvereinbarung).
- [ ] Webserver-Access-Logs (nginx/Apache) mit definierter, kurzer Aufbewahrungsfrist vorhalten und in der Dokumentation aufführen.
- [ ] Hinter einem Reverse Proxy `TRUSTED_PROXIES` in der `.env` setzen, sonst wird die IP des Proxys statt der des Nutzers protokolliert.
- [ ] `LOG_CHANNEL=stack` setzen, damit das Datenbank-Log (*Logs*) geschrieben wird.
- [ ] `LOG_LEVEL` in Produktion nicht auf `debug` stellen (sonst landen unnötig viele Details in den Logs).
- [ ] Kreis der Personen mit dem Recht `see security log` festlegen und dokumentieren.
- [ ] Optional: Sentry nur nutzen, wenn ein Auftragsverarbeitungsvertrag besteht; ohne `SENTRY_LARAVEL_DSN` werden keine Fehlerdaten an Dritte übermittelt.

---

## Updates

### Online-Update

Unter **Einstellungen → Online-Update** (Recht `manage updates`, Rolle Administrator) lassen sich neue Versionen aus dem Git-Repository prüfen und einspielen. Ausgeführt wird das Update vom Scheduler – also mit dem Benutzer des Cronjobs, nicht vom Webserver. Voraussetzung ist eine Installation per `git clone`.

Ablauf: Datenbanksicherung → Wartungsmodus → `git pull --ff-only` → `composer install` → Assets bauen → Migrationen → Caches leeren → Queue-Neustart → online.
`composer` und `npm` laufen nur, wenn sich die zugehörigen Dateien geändert haben. Schlägt ein Schritt fehl, bleibt die Anwendung im Wartungsmodus; der auslösende Admin sieht die Seite weiterhin.

```bash
php artisan updater:check          # nach Updates suchen
php artisan updater:run            # Update direkt ausführen
php artisan updater:run --full     # alle Schritte erzwingen
```

Wichtige Variablen (siehe `config/updater.php`):

| Variable | Standard | Bedeutung |
|---|---|---|
| `UPDATER_ENABLED` | `true` | Updater an/aus |
| `UPDATER_BRANCH` | – | Branch, von dem aktualisiert wird (z. B. `master`) |
| `UPDATER_PHP_BINARY`, `UPDATER_COMPOSER_BINARY`, `UPDATER_NPM_BINARY`, `UPDATER_MYSQLDUMP_BINARY`, `UPDATER_GIT_BINARY` | aus `PATH` | Pfade, falls der Cron-Benutzer sie nicht findet |
| `UPDATER_BACKUP` / `UPDATER_BACKUP_REQUIRED` | `true` / `true` | Datenbanksicherung vor dem Update; bei `REQUIRED` bricht das Update ohne Sicherung ab |
| `UPDATER_BACKUP_KEEP` | `5` | Anzahl aufbewahrter Sicherungen |
| `UPDATER_BUILD_ASSETS` | `true` | Assets mit npm bauen (auf `false`, wenn kein Node.js vorhanden) |
| `UPDATER_STEP_TIMEOUT` | `900` | Zeitlimit je Schritt (Sekunden) |
| `UPDATER_CHECK_CRON` | `17 */6 * * *` | Automatische Suche nach Updates |

Sicherungen und Protokolle liegen in `storage/app/updater`.

### Manuelles Update

Alternativ per Shell mit `deploy.sh` (vorher die Datenbank sichern):

```bash
sh deploy.sh
```

Das Skript führt dieselben Schritte aus und bricht beim ersten Fehler im Wartungsmodus ab. Nach Behebung erneut ausführen oder `php artisan up`.

### Hinweise zu einzelnen Versionen

- **Erklärungsquittungen (Elternabfragen):** Nach dem Update legt der Scheduler für bereits abgegebene Antworten und Bestätigungen automatisch Quittungen an (stündlich bis zu 500, nur solange welche fehlen; ohne E-Mails). Sofort und vollständig geht es mit `php artisan elternabfragen:quittungen-nacherfassen` (vorher ansehen mit `--dry-run`); danach `php artisan elternabfragen:verify --all`.

---

## UCS@school-Integration

Das ElternInfoBoard kann Eltern und Klassen automatisch aus einer [UCS@school](https://www.univention.de/produkte/ucsschool/)-Instanz über die **Kelvin REST API** abgleichen. Dadurch entfällt die manuelle Pflege der Klassenzuordnungen; Eltern melden sich per OIDC/Keycloak an.

### Voraussetzungen

- **Kelvin-Service-Account** in UCS (Leserechte auf Schüler- und Erziehungsberechtigtendaten)
- **OIDC-Client** in Keycloak (Eltern-Realm); Redirect-URI: `<APP_URL>/login/keycloak/callback`
- **Netzwerk**: Der Server muss den Kelvin-API-Host per HTTPS erreichen
- Der Abgleich läuft als Artisan-Befehl im Scheduler – ein Queue-Worker ist dafür nicht nötig

### Einrichtung

1. **Einstellungen → OIDC / Keycloak**: Base-URL, Realm, Client-ID/-Secret, Redirect-URI, erlaubte Maildomain, Rollen für Schülerkonten und ob unbekannte SSO-Nutzer automatisch angelegt werden.
2. **Einstellungen → UCS@school**: Kelvin-Base-URL (z. B. `https://ucs.schule.example/ucsschool/kelvin/v1`), Benutzername und Passwort, Schulkürzel, Abgleich aktivieren, Zeitplan (Standard 02:30 Uhr).
3. Verbindung testen: Test-Button im Tab oder `php artisan ucs:ping`
4. Probelauf: `php artisan sync:ucs-parents --dry-run`, danach ersten Abgleich starten (Button im Tab oder ohne `--dry-run`).

Die `UCS_*`-Variablen werden nur beim ersten `migrate` als Startwerte übernommen; danach gelten allein die Einstellungen in der Oberfläche. Die `KEYCLOAK_*`-Variablen dienen als Rückfallwerte, Einträge in der Oberfläche haben Vorrang. Details: `docs/ucs-kelvin-integration-konzept.md`.

---

## Eltern-App & MCP-Server

### Eltern-App

Die Eltern-App nutzt die App-API v1 (Dokumentation unter `/docs`, erzeugt mit Scribe). Für native Push-Mitteilungen:

| Variable | Bedeutung |
|---|---|
| `APNS_KEY_ID`, `APNS_TEAM_ID`, `APNS_KEY_PATH`, `APNS_BUNDLE_ID`, `APNS_PRODUCTION` | iOS: APNs-Schlüssel (`.p8`) aus dem Apple-Entwicklerkonto, z. B. in `storage/app/private/apns.p8` |
| `FCM_CREDENTIALS` | Android: Firebase-Service-Account-JSON (Spark-Tarif genügt) |
| `APP_SCHEME`, `APP_MIN_VERSION` | URL-Schema der App, minimal unterstützte App-Version |
| `APP_PRIVACY_PUBLISHER`, `APP_PRIVACY_CONTACT` | Angaben für die Datenschutzerklärung unter `/app/datenschutz` (Pflicht für die App-Stores) |

Einrichtung prüfen mit `php artisan push:check [email]`.

### MCP-Server

Ein lesender MCP-Endpunkt für Chatbots (`POST /api/v1/mcp`) ist standardmäßig aus und wird mit `MCP_ENABLED=true` freigeschaltet. Mit `MCP_DISABLED_TOOLS` lassen sich einzelne Werkzeuge (z. B. Krankmeldungen) sperren. Details: `docs/mcp-server.md`.

---

## Hilfreiche Artisan-Befehle

| Befehl | Zweck |
|---|---|
| `app:create-first-admin` | Ersten Administrator anlegen |
| `demo:setup` | Demo-Instanz mit Beispieldaten aufsetzen |
| `mail:test {email}` | Testmail senden |
| `push:check {email?}` | App-Push (FCM/APNs) prüfen |
| `schedule:list` | Geplante Aufgaben anzeigen |
| `updater:check` / `updater:run` | Online-Update |
| `media:make-private` | Altbestand von Mediendateien auf die private Disk umziehen (`--dry-run`) |
| `ucs:ping` / `sync:ucs-parents` | UCS@school testen bzw. abgleichen |
| `family:status` | Stand des Familienmodells anzeigen |
| `elternabfragen:verify {id?} --all` | Erklärungsquittungen (Hash-Kette) einer bzw. aller Abfragen prüfen; Exit-Code ≠ 0 bei Fehlern |
| `elternabfragen:quittungen-nacherfassen` | Fehlende Quittungen für ältere Antworten anlegen (`--dry-run`, `--limit=`); läuft nach dem Update automatisch |
| `stundenplan:import {datei}` | Stundenplan aus JSON importieren |
| `settings:clear-cache` | Einstellungs-Cache leeren |

---

## Entwicklung & Tests

Für die lokale Entwicklung `APP_ENV=local`, `APP_DEBUG=true` und die Dev-Abhängigkeiten installieren (`composer install` ohne `--no-dev`). Alle Prozesse (Server, Queue, Logs, Vite) starten mit:

```bash
composer run dev
```

Oder einzeln:

```bash
php artisan serve          # Webserver
php artisan queue:listen   # Queue-Worker
npm run dev                # Vite
php artisan pail           # Log-Viewer
```

Tests:

```bash
composer run test
php artisan test --parallel
```

---

## Theme-System

Farben werden unter *Einstellungen → Design*, Standard-Theme und Logo unter *Einstellungen → Allgemein* gesetzt. Eigene Theme-Klassen (abgeleitet von `App\Themes\AbstractTheme`) lassen sich ohne Änderung am Core in `config/themes.php` registrieren.

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
| Medienverwaltung | Spatie Laravel Medialibrary (private Disk) |
| Authentifizierung | Laravel Sanctum + Socialite (Keycloak), Passwordless-Login |
| Push-Benachrichtigungen | laravel-notification-channels/webpush, APNs, FCM |
| Audit-Log | owen-it/laravel-auditing |
| PDF-Export | barryvdh/laravel-dompdf |
| Excel-Import/Export | Maatwebsite/Laravel-Excel |
| API-Dokumentation | Scribe |
| MCP | laravel/mcp |
| Fehlerberichte | Sentry (optional) |
