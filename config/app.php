<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Facade;

return [

    'timezone' => 'Europe/Berlin',

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

    // WICHTIG: Diese Werte müssen in der .env gesetzt sein!
    // Kein Fallback auf das Datum mehr (war trivial vorhersagbar: dmY-Format).
    // Wenn nicht gesetzt → Import wird im Controller mit einem Fehler abgebrochen.
    'import_eltern' => env('PW_IMPORT_ELTERN'),

    'import_aufnahme' => env('PW_IMPORT_AUFNAHME'),

    'import_mitarbeiter' => env('PW_IMPORT_MITARBEITER'),

    'import_verein' => env('PW_IMPORT_VEREIN'),

    'enable_reactions' => env('ENABLE_REACTIONS', true),


];
