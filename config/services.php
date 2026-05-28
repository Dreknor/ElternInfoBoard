<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
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
