<?php

/*
|--------------------------------------------------------------------------
| Eigene Skripte (z. B. Chatbot-Widget) pro Instanz
|--------------------------------------------------------------------------
|
| Erlaubt es, auf einer einzelnen Instanz ein JavaScript-Snippet eines
| Drittanbieters (z. B. Chatbot) in die Weboberfläche einzubinden.
|
| Standardmäßig ausgeschaltet: Ohne Freischaltung erscheint weder die
| Einstellungsseite noch wird etwas ausgegeben oder aus der Datenbank gelesen.
| Freischaltung in der .env der Instanz:
|
|   CUSTOM_SCRIPTS_ENABLED=true
|
| Danach können Nutzer mit der Berechtigung "edit settings" das Snippet unter
| Einstellungen → Eigene Skripte hinterlegen. Auf false setzen schaltet die
| Ausgabe sofort ab (Notaus), das gespeicherte Snippet bleibt erhalten.
|
*/

return [

    'enabled' => (bool) env('CUSTOM_SCRIPTS_ENABLED', false),

    /** Maximale Länge des Snippets in Zeichen. */
    'max_length' => 20000,

];
