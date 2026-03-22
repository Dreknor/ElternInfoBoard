<?php

return [

    'timezone' => 'Europe/Berlin',

    'aliases' => \Illuminate\Support\Facades\Facade::defaultAliases()->merge([
        'Excel' => Maatwebsite\Excel\Facades\Excel::class,
        'PDF' => Barryvdh\DomPDF\Facade::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
    ])->toArray(),

    'directories_elternrat' => explode(',', env('ELTERNRAT_DIRS', 'Allgemein,Protokolle')),

    'logo' => env('APP_LOGO', 'logo.png'),

    'favicon' => env('APP_FAVICON', 'favicon.ico'),

    'logo_small' => env('APP_LOGO_SMALL', 'app_logo.png'),

    'mitarbeiterboard' => env('LINK_MITARBEITERBOARD'),

    'mitarbeiterboard_api_key' => env('API_KEY_MITARBEITERBOARD'),

    'api_key' => env('API_KEY'),

    'enable_reactions' => env('ENABLE_REACTIONS', true),

    'keycloak' => [
        'enabled' => env('KEYCLOAK_ENABLED', false),
        'url' => env('KEYCLOAK_URL', 'http://localhost:8080/auth'),
        'realm' => env('KEYCLOAK_REALM', 'elterninfoboard'),
        'client_id' => env('KEYCLOAK_CLIENT_ID', 'elterninfoboard'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET', now()->timestamp),
        'mail_domain' => explode('|', env('KEYCLOAK_MAIL_DOMAIN', explode('@', env('MAIL_FROM_ADDRESS'))[1])),
    ],

];
