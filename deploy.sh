#!/bin/sh
# Deployment ElternInfoBoard
#
# Vorher: Datenbank sichern (siehe docs/konzept-kontoverknuepfung-pruefung.md §5).
# Bricht beim ersten Fehler ab und bleibt dann im Wartungsmodus, damit ein halb
# migrierter Stand nicht online geht. Nach Behebung: Skript erneut ausführen
# oder „php artisan up“.
set -e

# activate maintenance mode
php artisan down --retry=60

# update source code
git pull --ff-only

# update PHP dependencies
composer install --no-interaction --prefer-dist
# --no-interaction Do not ask any interactive question
# --prefer-dist  Forces installation from package dist even for dev versions.

# veraltete Liste erkannter Settings-Klassen verwerfen (u. a. entfernte KeyCloakSetting)
php artisan settings:clear-discovered

# build frontend assets (public/build ist nicht versioniert)
if command -v npm >/dev/null 2>&1; then
    npm ci --no-audit --no-fund
    npm run build
else
    echo "WARNUNG: npm nicht gefunden – Assets (public/build) bitte separat bauen und hochladen."
fi

# update database: Schema- und Settings-Migrationen (database/settings läuft mit „migrate“)
php artisan migrate --force
# --force  Required to run when in production.

# seed UCS permissions (idempotent; legt "manage ucs sync" an + weist Admin zu)
php artisan db:seed --class=UcsSyncPermissionSeeder --force

# clear caches so updated config, routes, views and settings are picked up
# (bewusst kein cache:clear – Sitzungen/Sperren im Cache bleiben erhalten)
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan settings:clear-cache

# restart queue workers (für Jobs wie ProcessRemindersJob etc.)
php artisan queue:restart

# Stand des Familienmodells anzeigen (ändert nichts)
php artisan family:status || true

# stop maintenance mode
php artisan up
