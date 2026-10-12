<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Website-URL
    |--------------------------------------------------------------------------
    |
    | Adresse der WordPress-Seite, mit oder ohne "https://" (z. B. "www.schule.de").
    |
    */

    'wp_url' => env('WP_URL'),

    /*
    |--------------------------------------------------------------------------
    | Wordpress User
    |--------------------------------------------------------------------------
    */

    'wp_username' => env('WP_USER_NAME'),
    'wp_password' => env('WP_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Kategorien
    |--------------------------------------------------------------------------
    |
    | Kommagetrennte IDs der WordPress-Kategorien, in die Beiträge einsortiert
    | werden (z. B. "3" für "Aktuelles"). Leer = Standardkategorie von WordPress.
    |
    */

    'categories' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('WP_CATEGORIES', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Dateianhänge
    |--------------------------------------------------------------------------
    |
    | Sollen Dateianhänge (z. B. PDF-Elternbriefe) als Download mit
    | veröffentlicht werden? Bilder werden immer übertragen.
    | Achtung: Die Dateien sind danach öffentlich auf der Homepage abrufbar.
    |
    */

    'push_files' => (bool) env('WP_PUSH_FILES', false),

    /*
    |--------------------------------------------------------------------------
    | Zeitlimit
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('WP_TIMEOUT', 60),

];
