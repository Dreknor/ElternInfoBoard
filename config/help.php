<?php

/*
|--------------------------------------------------------------------------
| Hilfe-System Konfiguration
|--------------------------------------------------------------------------
|
| Hier werden alle Hilfe-Themen registriert. Jedes Topic kann optional
| auf eine Permission, eine Rolle oder einen Gate-Check gemappt werden.
| Über das `routes`-Array werden Topics kontextsensitiv angezeigt
| (Pattern via Str::is gegen Routenname & URI).
|
| Die Inhalte liegen standardmäßig als Markdown unter
| resources/help/{slug}.md – alternativ kann via "site_id" auf eine
| bestehende DB-Site verwiesen werden.
|
*/

return [

    // Cache-Dauer für gerenderte Markdown-Inhalte (Sekunden). 0 = aus.
    'cache_ttl' => 3600,

    // Verzeichnis mit Markdown-Inhalten (relativ zu resource_path()).
    'content_path' => 'help',

    // Gruppen für die Übersichtsseite (Reihenfolge & Anzeigename).
    'groups' => [
        'erste-schritte' => 'Erste Schritte',
        'familie'        => 'Familie & Kinder',
        'kommunikation'  => 'Kommunikation',
        'organisation'   => 'Schulalltag & Organisation',
        'verwaltung'     => 'Verwaltung & Administration',
        'konto'          => 'Konto & Datenschutz',
    ],

    /*
    |--------------------------------------------------------------------
    | Topics
    |--------------------------------------------------------------------
    | Felder:
    |  - slug:       eindeutiger URL-Slug & Markdown-Dateiname
    |  - title:      Anzeigename
    |  - excerpt:    Kurzbeschreibung (Karten-Untertitel)
    |  - icon:       FontAwesome-Klasse (z. B. "fas fa-home")
    |  - group:      Gruppen-Key (siehe oben)
    |  - permission: optional, Spatie-Permission-Name (any -> sichtbar)
    |  - role:       optional, Rollenname
    |  - routes:     Patterns für Kontext-Match (Routenname ODER URI)
    |  - order:      Sortierung innerhalb Gruppe
    |  - site_id:    optional, leitet auf bestehende DB-Site weiter
    |  - file:       optional, abweichender Markdown-Dateiname
    */
    'topics' => [

        [
            'slug'    => 'erste-schritte',
            'title'   => 'Erste Schritte',
            'excerpt' => 'Willkommen! So findest du dich in der App schnell zurecht.',
            'icon'    => 'fas fa-rocket',
            'group'   => 'erste-schritte',
            'routes'  => ['dashboard', '/', 'home'],
            'order'   => 10,
        ],

        [
            'slug'    => 'dashboard',
            'title'   => 'Das Dashboard verstehen',
            'excerpt' => 'Was bedeuten die Kacheln auf der Startseite?',
            'icon'    => 'fas fa-th-large',
            'group'   => 'erste-schritte',
            'routes'  => ['dashboard', '/'],
            'order'   => 20,
        ],

        [
            'slug'    => 'nachrichten-lesen',
            'title'   => 'Nachrichten lesen',
            'excerpt' => 'Beiträge öffnen, Reaktionen geben, Lesebestätigungen abgeben.',
            'icon'    => 'fas fa-envelope-open-text',
            'group'   => 'kommunikation',
            'routes'  => ['nachrichten*', 'post*', 'posts*'],
            'order'   => 10,
        ],

        [
            'slug'       => 'posts-erstellen',
            'title'      => 'Beiträge erstellen',
            'excerpt'    => 'Eltern und Gruppen einen neuen Beitrag senden.',
            'icon'       => 'fas fa-pen-to-square',
            'group'      => 'kommunikation',
            'permission' => 'create posts',
            'routes'     => ['posts/create*', 'posts/*/edit*'],
            'order'      => 20,
        ],

        [
            'slug'    => 'messenger',
            'title'   => 'Messenger / Direktnachrichten',
            'excerpt' => 'Privat mit anderen Eltern und Mitarbeitern schreiben.',
            'icon'    => 'fas fa-comments',
            'group'   => 'kommunikation',
            'routes'  => ['messenger*'],
            'order'   => 30,
        ],

        [
            'slug'    => 'krankmeldung',
            'title'   => 'Kind krankmelden',
            'excerpt' => 'Schritt-für-Schritt-Anleitung für die Krankmeldung.',
            'icon'    => 'fas fa-notes-medical',
            'group'   => 'familie',
            'routes'  => ['krankmeldung*'],
            'order'   => 10,
        ],

        [
            'slug'    => 'schickzeiten',
            'title'   => 'Schickzeiten & Abholung',
            'excerpt' => 'Abholzeiten festlegen und Abholberechtigte verwalten.',
            'icon'    => 'fas fa-clock',
            'group'   => 'familie',
            'routes'  => ['schickzeiten*', 'anwesenheit*', 'checkIn*'],
            'order'   => 20,
        ],

        [
            'slug'    => 'kinder-verwalten',
            'title'   => 'Kinder verwalten',
            'excerpt' => 'Kind-Profile anlegen, bearbeiten oder entfernen.',
            'icon'    => 'fas fa-children',
            'group'   => 'familie',
            'routes'  => ['child*', 'care/children*'],
            'order'   => 30,
        ],

        [
            'slug'    => 'rueckmeldungen-abgeben',
            'title'   => 'Rückmeldungen abgeben',
            'excerpt' => 'Auf Abfragen antworten, Termine wählen, Dateien hochladen.',
            'icon'    => 'fas fa-clipboard-check',
            'group'   => 'organisation',
            'routes'  => ['userrueckmeldung*', 'rueckmeldung/show*'],
            'order'   => 10,
        ],

        [
            'slug'       => 'pflichtstunden',
            'title'      => 'Pflichtstunden erfassen',
            'excerpt'    => 'Geleistete Elternstunden eintragen und einreichen.',
            'icon'       => 'fas fa-hourglass-half',
            'group'      => 'organisation',
            'permission' => 'view Pflichtstunden',
            'routes'     => ['pflichtstunden*'],
            'order'      => 20,
        ],

        [
            'slug'       => 'stundenplan',
            'title'      => 'Stundenplan ansehen',
            'excerpt'    => 'Den aktuellen Stunden- und Vertretungsplan einsehen.',
            'icon'       => 'fas fa-calendar-week',
            'group'      => 'organisation',
            'permission' => 'view stundenplan',
            'routes'     => ['stundenplan*', 'vertretungsplan*'],
            'order'      => 30,
        ],

        [
            'slug'       => 'elternrat',
            'title'      => 'Elternrat-Bereich',
            'excerpt'    => 'Diskussionen, Termine und Dateien für den Elternrat.',
            'icon'       => 'fas fa-people-group',
            'group'      => 'organisation',
            'permission' => 'view elternrat',
            'routes'     => ['elternrat*'],
            'order'      => 40,
        ],

        [
            'slug'       => 'verwaltung-rueckmeldungen',
            'title'      => 'Rückmeldungen verwalten',
            'excerpt'    => 'Abfragen erstellen, auswerten und Erinnerungen senden.',
            'icon'       => 'fas fa-list-check',
            'group'      => 'verwaltung',
            'permission' => 'manage rueckmeldungen',
            'routes'     => ['rueckmeldungen*'],
            'order'      => 10,
        ],

        [
            'slug'       => 'verwaltung-schickzeiten',
            'title'      => 'Schickzeiten verwalten',
            'excerpt'    => 'Abholzeiten anderer Eltern einsehen und anpassen.',
            'icon'       => 'fas fa-user-clock',
            'group'      => 'verwaltung',
            'permission' => 'edit schickzeiten',
            'routes'     => ['verwaltung/schickzeiten*'],
            'order'      => 20,
        ],

        [
            'slug'       => 'benutzer-verwalten',
            'title'      => 'Benutzer & Rollen',
            'excerpt'    => 'Benutzerkonten anlegen, bearbeiten und Rollen zuweisen.',
            'icon'       => 'fas fa-users-gear',
            'group'      => 'verwaltung',
            'permission' => 'edit user',
            'routes'     => ['users*', 'user/*', 'roles*', 'permission*'],
            'order'      => 30,
        ],

        [
            'slug'       => 'gruppen-verwalten',
            'title'      => 'Gruppen / Klassen',
            'excerpt'    => 'Gruppen anlegen und Mitglieder zuordnen.',
            'icon'       => 'fas fa-layer-group',
            'group'      => 'verwaltung',
            'permission' => 'edit groups',
            'routes'     => ['groups*'],
            'order'      => 40,
        ],

        [
            'slug'    => 'datenschutz',
            'title'   => 'Datenschutz & Datenexport',
            'excerpt' => 'Welche Daten gespeichert werden und wie du sie exportierst.',
            'icon'    => 'fas fa-shield-halved',
            'group'   => 'konto',
            'routes'  => ['datenschutz*'],
            'order'   => 10,
        ],

        [
            'slug'    => 'konto-einstellungen',
            'title'   => 'Konto & Benachrichtigungen',
            'excerpt' => 'Profil bearbeiten, Passwort ändern, Push-Benachrichtigungen.',
            'icon'    => 'fas fa-user-gear',
            'group'   => 'konto',
            'routes'  => ['profile*', 'einstellungen*', 'settings/notification*'],
            'order'   => 20,
        ],

    ],
];
