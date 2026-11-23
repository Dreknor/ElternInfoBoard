<?php

/*
|--------------------------------------------------------------------------
| Eigene Skripte (z. B. Chatbot-Widget) pro Instanz
|--------------------------------------------------------------------------
|
| Erlaubt es, auf einer einzelnen Instanz eigene HTML/JavaScript-Snippets
| (z. B. Chatbot eines Drittanbieters) in die Weboberfläche einzubinden –
| beliebig viele, jeweils begrenzbar auf Seiten (Pfadmuster), Kontoarten
| (Gäste, angemeldete Konten, Schülerkonten) und Rollen.
|
| Standardmäßig ausgeschaltet: Ohne Freischaltung erscheint weder die
| Einstellungsseite noch wird etwas ausgegeben oder aus der Datenbank gelesen.
| Freischaltung in der .env der Instanz:
|
|   CUSTOM_SCRIPTS_ENABLED=true
|
| Danach können Nutzer mit der Berechtigung "edit settings" die Skripte unter
| Einstellungen → Eigene Skripte pflegen. Auf false setzen schaltet die
| Ausgabe sofort ab (Notaus), die gespeicherten Skripte bleiben erhalten.
|
*/

return [

    'enabled' => (bool) env('CUSTOM_SCRIPTS_ENABLED', false),

    /** Maximale Länge eines Snippets in Zeichen (je Skript). */
    'max_length' => 20000,

];
