<?php

/*
|--------------------------------------------------------------------------
| Instanz-Freischaltung von Modulen
|--------------------------------------------------------------------------
|
| Module in "restricted" sind nur auf Instanzen verfügbar, auf denen sie
| per Umgebungsvariable freigeschaltet wurden. Auf allen anderen Instanzen
| gelten sie als inaktiv und erscheinen nicht in der Modulverwaltung, so
| dass der Schuladmin sie dort nicht selbst aktivieren kann.
|
| Freischaltung in der .env der Instanz (kommagetrennt, Name wie in
| settings_modules.setting):
|
|   MODULES_UNLOCKED="Messenger,Elternabfragen"
|
*/

return [

    'restricted' => [
        // 'Messenger',
    ],

    'unlocked' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MODULES_UNLOCKED', ''))
    ))),

];
