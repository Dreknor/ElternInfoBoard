#!/bin/sh
# activate maintenance mode
php artisan down
# update source code
git pull
# update PHP dependencies
composer install --no-interaction --prefer-dist
# --no-interaction Do not ask any interactive question
# --no-dev  Disables installation of require-dev packages.
# --prefer-dist  Forces installation from package dist even for dev versions.
# update database (schema migrations)
php artisan migrate --force
# --force  Required to run when in production.
# apply Spatie Laravel Settings migrations (UCS + alle anderen Einstellungsgruppen)
php artisan settings:migrate --force
# seed UCS permissions (idempotent; legt "manage ucs sync" an + weist Admin zu)
php artisan db:seed --class=UcsSyncPermissionSeeder --force
# restart queue workers (für andere Jobs wie ProcessRemindersJob etc.)
php artisan queue:restart
# clear config/route cache so updated settings are picked up
php artisan config:clear
php artisan route:clear
# stop maintenance mode
php artisan up
