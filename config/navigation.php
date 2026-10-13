<?php

/*
|--------------------------------------------------------------------------
| Kompaktes Seitenmenü
|--------------------------------------------------------------------------
|
| Gruppierung der Menüeinträge für die Darstellung 'compact'
| (GeneralSetting::$nav_style bzw. Nutzereinstellung 'nav_style').
|
| Die Zuordnung erfolgt über den Modulnamen (settings_modules.setting).
| Ein Modul kann die Zuordnung über options.nav.group bzw.
| options.adm-nav.group selbst überschreiben. Der Wert 'top' zeigt den
| Eintrag ungruppiert direkt unter dem Dashboard an.
|
| Module ohne Zuordnung landen in der Gruppe 'weitere' bzw. 'adm-weitere'.
| Gruppen mit nur einem sichtbaren Eintrag werden nicht eingeklappt,
| sondern als einfacher Link dargestellt.
|
*/

return [

    'styles' => [
        'classic' => 'Klassisch (alle Einträge untereinander)',
        'compact' => 'Kompakt (gruppiert und einklappbar)',
    ],

    'groups' => [
        'kommunikation' => ['label' => 'Kommunikation', 'icon' => 'fas fa-comments'],
        'planung' => ['label' => 'Termine & Pläne', 'icon' => 'far fa-calendar-alt'],
        'organisation' => ['label' => 'Organisation', 'icon' => 'fas fa-clipboard-list'],
        'informationen' => ['label' => 'Infos & Dateien', 'icon' => 'fas fa-folder-open'],
        'weitere' => ['label' => 'Weitere', 'icon' => 'fas fa-ellipsis-h'],
    ],

    'admin_groups' => [
        'adm-inhalte' => ['label' => 'Inhalte erstellen', 'icon' => 'fas fa-plus-circle'],
        'adm-personen' => ['label' => 'Personen & Rechte', 'icon' => 'fas fa-users-cog'],
        'adm-organisation' => ['label' => 'Organisation', 'icon' => 'fas fa-sitemap'],
        'adm-system' => ['label' => 'System', 'icon' => 'fas fa-cogs'],
        'adm-weitere' => ['label' => 'Weitere', 'icon' => 'fas fa-ellipsis-h'],
    ],

    'fallback_group' => 'weitere',
    'admin_fallback_group' => 'adm-weitere',

    // Modulname => Gruppe (Hauptmenü)
    'modules' => [
        'Nachrichten' => 'top',
        'Termine' => 'top',

        'Eltern-Nachrichten' => 'kommunikation',
        'Kontakt' => 'kommunikation',
        'Elternrat' => 'kommunikation',
        'Gruppen' => 'kommunikation',

        'Stundenplan' => 'planung',
        'Vertretungsplan' => 'planung',
        'Familienübersicht' => 'planung',
        'Reinigung' => 'planung',
        'Pflichtstunden' => 'planung',

        'Krankmeldung' => 'organisation',
        'Listen' => 'organisation',
        'Arbeitsgemeinschaften' => 'organisation',

        'Archiv' => 'informationen',
        'Dateien' => 'informationen',
        'Seiten' => 'informationen',
        'externe Angebote' => 'informationen',
        'Schulsoftware' => 'informationen',
    ],

    // Modulname => Gruppe (Verwaltung)
    'admin_modules' => [
        'Nachrichten' => 'adm-inhalte',
        'Termine' => 'adm-inhalte',
        'Dateien' => 'adm-inhalte',
        'Listen' => 'adm-inhalte',
        'Seiten' => 'adm-inhalte',
        'Losung' => 'adm-inhalte',
        'Krankmeldung' => 'adm-inhalte',
        'meldepfl. Erkrankungen' => 'adm-inhalte',
        'bearbeite Rueckmeldungen' => 'adm-inhalte',

        'Benutzerverwaltung' => 'adm-personen',
        'Rechte' => 'adm-personen',
        'Kinderverwaltung' => 'adm-personen',
        'Anwesenheitsliste' => 'adm-personen',
        'Moderation' => 'adm-personen',

        'Pflichtstunden' => 'adm-organisation',
        'Arbeitsgemeinschaften' => 'adm-organisation',

        'Modules' => 'adm-system',
        'Settings' => 'adm-system',
        'Logs' => 'adm-system',
    ],

];
