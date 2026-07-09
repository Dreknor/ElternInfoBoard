<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    /*
    |--------------------------------------------------------------------------
    | FreeScout Support-Ticketsystem
    |--------------------------------------------------------------------------
    | Credentials für die FreeScout REST-API.
    | FREESCOUT_URL        – Basis-URL der FreeScout-Instanz (ohne /api)
    | FREESCOUT_API_KEY    – API-Key aus FreeScout → Account → API-Keys
    | FREESCOUT_MAILBOX_ID – ID der Ziel-Mailbox in FreeScout
    */
    'freescout' => [
        'url'        => env('FREESCOUT_URL'),
        'api_key'    => env('FREESCOUT_API_KEY'),
        'mailbox_id' => (int) env('FREESCOUT_MAILBOX_ID', 0),

        /*
         | queue_sync  – true  → Job läuft synchron im selben Prozess (kein Worker nötig)
         |             – false → Job wird in die Queue gestellt (Worker muss laufen)
         | queue_name  – Name der Queue, die der Worker abarbeitet
         */
        'queue_sync' => env('FREESCOUT_QUEUE_SYNC', false),
        'queue_name' => env('FREESCOUT_QUEUE_NAME', 'freescout'),
    ],

    'keycloak' => [
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect' => env('KEYCLOAK_REDIRECT_URI'),
        'base_url' => env('KEYCLOAK_BASE_URL'),     // https://auth.dllp.schule
        'realms' => env('KEYCLOAK_REALM', 'master'), // ucs (note: config key is 'realms' plural!)
        'enabled' => env('KEYCLOAK_ENABLED', false),
        'button_text' => env('KEYCLOAK_BUTTON_TEXT', 'Login mit SSO'),
        'mail_domain' => env('KEYCLOAK_MAILDOMAIN', '*'),
    ],

];
