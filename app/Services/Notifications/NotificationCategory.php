<?php

namespace App\Services\Notifications;

use App\Model\User;
use App\Services\App\Family;
use App\Services\App\Modules;
use App\Services\Push\PushTarget;

/**
 * Kategorien, für die Nutzer ihre Benachrichtigungskanäle wählen können.
 *
 * Jede Benachrichtigung (Glocke) wird über `type` bzw. `url` einer Kategorie zugeordnet.
 * `system` (Hinweise der Verwaltung, Fehler, Changelog …) ist nicht abschaltbar.
 */
final class NotificationCategory
{
    public const NACHRICHTEN = 'nachrichten';

    public const ERINNERUNGEN = 'erinnerungen';

    public const TERMINE = 'termine';

    public const VERTRETUNGSPLAN = 'vertretungsplan';

    public const MESSENGER = 'messenger';

    public const HORT = 'hort';

    public const ORGANISATION = 'organisation';

    public const ELTERNRAT = 'elternrat';

    public const SYSTEM = 'system';

    public const CHANNELS = ['app', 'web', 'mail'];

    /** Recht, die E-Mail-Zusammenfassung für neue Nachrichten abzubestellen. */
    public const DISABLE_NEWS_MAIL_PERMISSION = 'disable news mail';

    /**
     * Beschriftung, Beschreibung und verfügbare Kanäle. E-Mail nur dort, wo das Board
     * tatsächlich E-Mails versendet (Nachrichten = tägliche/wöchentliche Zusammenfassung).
     *
     * @return array<string, array{label: string, description: string, channels: string[]}>
     */
    public static function definitions(): array
    {
        return [
            self::NACHRICHTEN => [
                'label' => 'Neue Nachrichten',
                'description' => 'Neue Beiträge und externe Angebote. E-Mail: Zusammenfassung im gewählten Rhythmus.',
                'channels' => ['app', 'web', 'mail'],
            ],
            self::ERINNERUNGEN => [
                'label' => 'Erinnerungen',
                'description' => 'Fehlende Rückmeldungen und Lesebestätigungen.',
                'channels' => ['app', 'web', 'mail'],
            ],
            self::TERMINE => [
                'label' => 'Termine & Listen',
                'description' => 'Neue Termine, neue Listen und Änderungen an Ihren Eintragungen.',
                'channels' => ['app', 'web'],
            ],
            self::VERTRETUNGSPLAN => [
                'label' => 'Vertretungsplan',
                'description' => 'Änderungen für die Klassen Ihrer Kinder.',
                'channels' => ['app', 'web'],
            ],
            self::MESSENGER => [
                'label' => 'Eltern-Nachrichten',
                'description' => 'Neue Nachrichten im Messenger.',
                'channels' => ['app', 'web'],
            ],
            self::HORT => [
                'label' => 'Hort & Krankmeldungen',
                'description' => 'Anwesenheit, Anwesenheitsabfragen, Schickzeiten und Krankmeldungen.',
                'channels' => ['app', 'web', 'mail'],
            ],
            self::ORGANISATION => [
                'label' => 'Reinigung, Pflichtstunden & AGs',
                'description' => 'Erinnerungen an den Reinigungsdienst und Rückmeldungen zu Pflichtstunden.',
                'channels' => ['app', 'web', 'mail'],
            ],
            self::ELTERNRAT => [
                'label' => 'Elternrat',
                'description' => 'Diskussionen und Termine des Elternrats.',
                'channels' => ['app', 'web', 'mail'],
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function supports(string $category, string $channel): bool
    {
        return in_array($channel, self::definitions()[$category]['channels'] ?? [], true);
    }

    /**
     * Kanal ist für den Nutzer fest aktiv und kann nicht abgewählt werden.
     */
    public static function locked(User $user, string $category, string $channel): bool
    {
        return $category === self::NACHRICHTEN
            && $channel === 'mail'
            && ! $user->can(self::DISABLE_NEWS_MAIL_PERMISSION);
    }

    /**
     * Kategorien, die für den Nutzer relevant sind (Module/Rechte wie in der App).
     *
     * @return string[]
     */
    public static function visibleFor(User $user): array
    {
        $modules = array_flip(Modules::activeFor($user));
        $has = fn (string $module) => isset($modules[$module]);

        return array_values(array_filter(self::keys(), fn (string $key) => match ($key) {
            self::TERMINE => $has('Termine') || $has('Listen'),
            self::VERTRETUNGSPLAN => $has('Vertretungsplan') && $user->can('view vertretungsplan'),
            self::MESSENGER => $has('Eltern-Nachrichten') && $user->can('use messenger'),
            self::HORT => $has('Krankmeldung') || Family::childIds($user) !== [],
            self::ORGANISATION => $has('Reinigung') || $has('Pflichtstunden') || $has('Arbeitsgemeinschaften'),
            self::ELTERNRAT => $has('Elternrat') && $user->can('view elternrat'),
            default => true,
        }));
    }

    /**
     * Kategorie einer Benachrichtigung aus Typ (Freitext der Aufrufer) und Ziel-URL.
     */
    public static function resolve(?string $type, ?string $url = null): string
    {
        $byType = match (mb_strtolower(trim((string) $type))) {
            'nachrichten', 'ex. angebot', 'news', 'post' => self::NACHRICHTEN,
            'erinnerung', 'lesebestätigung', 'rückmeldung' => self::ERINNERUNGEN,
            'termine', 'termin', 'listen', 'listen eintragung', 'liste' => self::TERMINE,
            'vertretung', 'vertretungsplan' => self::VERTRETUNGSPLAN,
            'messenger', 'conversation' => self::MESSENGER,
            'anwesenheit', 'anwesenheitsabfrage', 'krankmeldung', 'attendance', 'child' => self::HORT,
            'pflichtstunden', 'reinigung', 'ags' => self::ORGANISATION,
            'elternrat' => self::ELTERNRAT,
            default => null,
        };
        if ($byType) {
            return $byType;
        }

        return match (PushTarget::fromUrl($url)['type'] ?? null) {
            'post' => self::NACHRICHTEN,
            'liste', 'termin' => self::TERMINE,
            'vertretungsplan' => self::VERTRETUNGSPLAN,
            'conversation' => self::MESSENGER,
            'attendance', 'krankmeldung', 'child' => self::HORT,
            'pflichtstunden', 'reinigung', 'ags' => self::ORGANISATION,
            'elternrat' => self::ELTERNRAT,
            default => self::SYSTEM,
        };
    }
}
