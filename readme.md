<p align="center"><img src="https://mitarbeiter.esz-radebeul.de/img/logo.png" width="400"></p>

## Über das ElternInfoBoard

Das ElternInfoBoard ist ein Freizeitprojekt, welches entstanden ist um die Kommunikation des Evangelischen Schulzentrums Radebeul mit den Eltern der Schülerinnen und Schüler zu unterstützen. Es ermöglicht die Vorbereitung von Dienstberatungen online, in dem Themen durch Leitungen und Mitarbeiter im vorraus benannt, terminiert und priorisiert werden. Die Protokolle der besprochenen Themen werden direkt zu dem Thema abgelegt und sind somit jederzeit direkt abruf- und nachverfolgbar.
Es basiert auf dem [Laravel-Framework](https://laravel.com/).

## Nutzung

Obwohl das ElternInfoBoard ausschließlich für das Evangelische Schulzentrum Radebeul gedacht war, kann die Software frei für nicht-kommerzielle Projekte im Bereich der Bildung genutzt werden. Es gibt jedoch keinerlei Anspruch auf Support oder Haftung, sollten Schäden oder Probleme auftreten.
Änderungen und Weiterentwicklungen sind ebenfalls als Open-Source zur Verfügung zu stellen.

## Systemvoraussetzungen

* PHP 8
* Composer

## Installation

Nach dem Upload der Dateien auf den Server ist zunächst die Datei ".env.example" in ".env" umzubenennen und auszufüllen. 


```bash
cp .env.example .env
```

## Anpassung der .env

Die Datei per Texteditor öffnen und mindestens die angegebenen Daten ausfüllen:

```bash
APP_NAME => Wie die Anwendung dann benannt werden soll 
APP_ENV => in Produktivumgebungen unbedingt production einsetzen. Zum Testen local nutzen
APP_KEY => wird später durch den Befehl "php artisan key:generate" gesetzt

APP_DEBUG => sollte false sein. Wenn Fehler auftreten kann der Wert kurzfristig auf true gesetzt werden, um ausführliche Fehlermeldungen zu erhalten. ACHTUNG: Sicherheitslücke!
APP_URL => URL durch die die Anwendung erreichbar ist. Wird als Grundlage zur Erstellung von Links benötigt

Wenn Stanardtpasswörter für den Import verwendet werden sollen, so können diese hier gesetzt werden
PW_IMPORT_AUFNAHME
PW_IMPORT_MITARBEITER
PW_IMPORT_VEREIN

Logo und Favicon ablegen unter /public/img and set name here
APP_LOGO    => eigenes Logo
APP_FAVICON => eigenes Icon (In der Adressleiste des Browsers)

Datenbankangaben sind zwingend erforderlich
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD

Hier werden die Angaben zur SMTP-Server für die E-Mails eingetragen (Zwingend)
MAIL_MAILER
MAIL_HOST
MAIL_PORT
MAIL_USERNAME
MAIL_PASSWORD
MAIL_ENCRYPTION

MAIL_FROM_ADDRESS => Welche E-Mail wird als Absender verwendet
MAIL_FROM_NAME => Name des Abenders
```

Anschließend die Installation durchführen:

```bash
composer install
```
```bash
php artisan key:generate
```

```bash
php artisan webpush:vapid
```

```bash
php artisan migrate
```
Während dem Erstellen der Datenbanktabellen wird ein erster Benutzer mit der in der .env-Datei angegebenen E-Mail erstellt. Als Kennwort dient das aktuelle Datum 8-stellig. Es muss mit dem ersten Login geändert werden.

Nun muss noch der CronJob angelegt werden, damit die automatisierten Prozesse für Benachrichtigungen und Mail-Versan laufen:

```bash
crontab -e
```

und dort eintragen:
```bash
* * * * * cd /your-project-path && php artisan schedule:run >> /dev/null 2>&1
```
