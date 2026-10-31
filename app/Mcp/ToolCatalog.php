<?php

namespace App\Mcp;

use App\Mcp\Tools\ApiEndpointTool;

/**
 * Alle Werkzeuge des Eltern-MCP-Servers. Jedes bildet genau eine lesende GET-Route der App-API ab
 * (`api/v1/*` bzw. die älteren `api/*`-Endpunkte). Schreibende Funktionen gibt es bewusst nicht.
 */
class ToolCatalog
{
    /** @return list<ApiEndpointTool> */
    public static function tools(): array
    {
        $id = fn (string $what): array => ['type' => 'integer', 'description' => "ID: {$what}", 'in' => 'path', 'min' => 1];
        $childFilter = ['type' => 'integer', 'description' => 'Nur dieses Kind (ID aus list_children).', 'min' => 1];

        return [
            new ApiEndpointTool('get_profile', 'Eigenes Profil des angemeldeten Elternteils.', 'api/v1/me'),
            new ApiEndpointTool('get_family', 'Familie: verknüpfte Erziehungsberechtigte und Kinder.', 'api/family'),
            new ApiEndpointTool('list_children', 'Eigene Kinder mit Klasse und Gruppe.', 'api/parent/children'),
            new ApiEndpointTool('get_dashboard', 'Startseite: offene Aufgaben, Kinder heute, nächste Termine, neueste Beiträge, Losung, Erkrankungen.', 'api/v1/parent/dashboard'),
            new ApiEndpointTool('get_open_todos', 'Offene Aufgaben (Lesebestätigungen, Rückmeldungen, Abfragen).', 'api/v1/parent/todo'),
            new ApiEndpointTool('list_notifications', 'Benachrichtigungen des Nutzers.', 'api/v1/notifications', [
                'unread' => ['type' => 'boolean', 'description' => 'Nur ungelesene.'],
            ]),
            new ApiEndpointTool('list_posts', 'Nachrichten/Beiträge der eigenen Gruppen (neueste zuerst, mit Cursor-Pagination).', 'api/v1/posts', [
                'filter' => ['type' => 'string', 'description' => '`todo` = nur Beiträge mit offener Aufgabe.', 'enum' => ['todo']],
                'archived' => ['type' => 'boolean', 'description' => 'Archivierte Beiträge.'],
                'month' => ['type' => 'string', 'description' => 'Archiv-Monat im Format YYYY-MM.', 'pattern' => '/^\d{4}-\d{2}$/'],
                'external' => ['type' => 'boolean', 'description' => 'Nur externe Angebote.'],
                'q' => ['type' => 'string', 'description' => 'Suche in Titel und Text (max. 100 Zeichen).'],
                'per_page' => ['type' => 'integer', 'description' => 'Einträge pro Seite (5–50).', 'min' => 5, 'max' => 50],
                'cursor' => ['type' => 'string', 'description' => '`meta.next_cursor` der vorherigen Antwort.'],
            ]),
            new ApiEndpointTool('get_post', 'Einzelner Beitrag mit Text, Anhängen, Rückmeldung und eigenen Antworten.', 'api/v1/posts/{post}', ['post' => $id('Beitrag')]),
            new ApiEndpointTool('get_post_poll', 'Abstimmung eines Beitrags mit Ergebnissen.', 'api/v1/posts/{post}/poll', ['post' => $id('Beitrag')]),
            new ApiEndpointTool('list_post_comments', 'Kommentare eines Beitrags.', 'api/v1/posts/{post}/comments', ['post' => $id('Beitrag')]),
            new ApiEndpointTool('list_termine', 'Termine im Zeitraum (Standard: heute bis +6 Monate, max. ein Jahr).', 'api/v1/termine', [
                'from' => ['type' => 'string', 'description' => 'Startdatum YYYY-MM-DD.', 'pattern' => '/^\d{4}-\d{2}-\d{2}$/'],
                'to' => ['type' => 'string', 'description' => 'Enddatum YYYY-MM-DD.', 'pattern' => '/^\d{4}-\d{2}-\d{2}$/'],
            ]),
            new ApiEndpointTool('list_listen', 'Offene Listen (Eintragungen, Terminlisten) mit eigenen Buchungen und freien Plätzen.', 'api/v1/listen'),
            new ApiEndpointTool('get_liste', 'Eine Liste mit Terminen bzw. Einträgen.', 'api/v1/listen/{liste}', ['liste' => $id('Liste')]),
            new ApiEndpointTool('get_stundenplan', 'Stundenplan eines eigenen Kindes für eine Woche inkl. Vertretungen.', 'api/v1/parent/children/{child}/stundenplan', [
                'child' => $id('Kind (aus list_children)'),
                'week' => ['type' => 'string', 'description' => 'ISO-Woche YYYY-Www, z. B. 2026-W40 (Standard: aktuelle Woche).', 'pattern' => '/^\d{4}-W\d{1,2}$/'],
            ]),
            new ApiEndpointTool('get_vertretungsplan', 'Vertretungsplan der eigenen Klassen.', 'api/v1/vertretungsplan'),
            new ApiEndpointTool('list_ags', 'Arbeitsgemeinschaften, für die die eigenen Kinder in Frage kommen.', 'api/v1/parent/ags'),
            new ApiEndpointTool('get_reinigung', 'Reinigungsplan der Familie (nur wenn das Modul aktiv ist).', 'api/v1/parent/reinigung'),
            new ApiEndpointTool('list_krankmeldungen', 'Aktuelle Krankmeldungen der eigenen Kinder.', 'api/parent/krankmeldungen'),
            new ApiEndpointTool('list_krankmeldungen_history', 'Vergangene Krankmeldungen der eigenen Kinder.', 'api/parent/krankmeldungen/history', [
                'limit' => ['type' => 'integer', 'description' => 'Maximale Anzahl (1–200, Standard 50).', 'min' => 1, 'max' => 200],
                'child_id' => $childFilter,
            ]),
            new ApiEndpointTool('list_schickzeiten', 'Schickzeiten der eigenen Kinder.', 'api/parent/schickzeiten'),
            new ApiEndpointTool('get_pflichtstunden', 'Eigene Pflichtstunden im aktuellen Zeitraum samt Einstellungen.', 'api/pflichtstunden'),
            new ApiEndpointTool('get_pflichtstunden_stats', 'Auswertung der Pflichtstunden (Soll, Ist, Restzahlung).', 'api/pflichtstunden/stats'),
            new ApiEndpointTool('list_infoseiten', 'Infoseiten der eigenen Gruppen.', 'api/v1/sites'),
            new ApiEndpointTool('get_infoseite', 'Eine Infoseite mit allen Inhaltsblöcken.', 'api/v1/sites/{site}', ['site' => $id('Infoseite')]),
            new ApiEndpointTool('get_elternrat', 'Elternrat: Themen, Termine mit eigener Zusage, eigene Aufgaben (nur mit Berechtigung).', 'api/v1/elternrat'),
            new ApiEndpointTool('get_elternrat_discussion', 'Ein Elternrat-Thema mit Kommentaren.', 'api/v1/elternrat/discussions/{discussion}', ['discussion' => $id('Thema')]),
            new ApiEndpointTool('list_files', 'Dateien und Anhänge (Name, Typ, Größe) der für den Nutzer sichtbaren Beiträge (Messenger-Anhänge stehen in get_messenger_messages).', 'api/files', [
                'search' => ['type' => 'string', 'description' => 'Filter nach Dateiname.'],
                'mime_type' => ['type' => 'string', 'description' => 'Filter nach MIME-Typ, z. B. application/pdf.'],
                'page' => ['type' => 'integer', 'description' => 'Seite (Standard 1).', 'min' => 1],
                'per_page' => ['type' => 'integer', 'description' => 'Einträge pro Seite (max. 100, Standard 15).', 'min' => 1, 'max' => 100],
            ]),
            new ApiEndpointTool('get_losung', 'Losung des Tages.', 'api/losungen'),
            new ApiEndpointTool('list_messenger_conversations', 'Messenger-Unterhaltungen mit ungelesenen Nachrichten (nur mit Messenger-Berechtigung).', 'api/v1/messenger/conversations'),
            new ApiEndpointTool('get_messenger_messages', 'Nachrichten einer Messenger-Unterhaltung (neueste zuerst, Cursor-Pagination) inkl. Name, Typ und Größe von Anhängen. Markiert die Unterhaltung NICHT als gelesen.', 'api/v1/messenger/conversations/{conversation}/messages', [
                'conversation' => $id('Unterhaltung (aus list_messenger_conversations)'),
                'cursor' => ['type' => 'string', 'description' => 'meta.next_cursor der vorherigen Antwort.'],
            ]),
            new ApiEndpointTool('get_messenger_unread_count', 'Anzahl ungelesener Messenger-Nachrichten.', 'api/v1/messenger/unread-count'),
        ];
    }
}


