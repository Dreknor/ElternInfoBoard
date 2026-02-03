<?php

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Facade;

return [

    'timezone' => 'Europe/Berlin',

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Laravel Framework Service Providers...
         */

        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

        Barryvdh\DomPDF\ServiceProvider::class,
        DevDojo\LaravelReactions\Providers\ReactionsServiceProvider::class,

        // eigene
        App\Providers\ComposerServiceProvider::class,
        // App\Providers\KeycloakProvider::class,
        App\Providers\SettingsServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
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

    'import_eltern' => env('PW_IMPORT_ELTERN', Carbon::now()->format('dmY')),

    'import_aufnahme' => env('PW_IMPORT_AUFNAHME', Carbon::now()->format('dmY')),

    'import_mitarbeiter' => env('PW_IMPORT_MITARBEITER', Carbon::now()->format('dmY')),

    'import_verein' => env('PW_IMPORT_VEREIN', Carbon::now()->format('dmY')),

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
