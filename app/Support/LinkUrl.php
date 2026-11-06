<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Prüfung und Aufbereitung der Ziele im Modul „Links“.
 *
 * Erlaubt sind nur:
 *  - http(s)-Adressen (ohne Zugangsdaten in der URL),
 *  - mailto: und tel:,
 *  - interne Pfade dieser Instanz, beginnend mit genau einem „/“.
 * Alles andere (javascript:, data:, „//host“ …) wird abgelehnt.
 */
class LinkUrl
{
    public const MAX_LENGTH = 2048;

    /** Eingabe vereinheitlichen: trimmen, „www.…“ bzw. „schule.de/…“ zu https:// ergänzen. */
    public static function normalize(?string $url): string
    {
        $url = trim((string) $url);

        // Telefonnummern werden gern mit Leerzeichen eingegeben: „tel:+49 351 123456“.
        if (preg_match('/^tel:/i', $url)) {
            return 'tel:'.preg_replace('/\s+/u', '', substr($url, 4));
        }

        if ($url === '' || str_starts_with($url, '/')) {
            return $url;
        }

        if (! preg_match('/^[a-z][a-z0-9+.\-]*:/i', $url) && preg_match('/^[\p{L}0-9][\p{L}0-9\-]*(\.[\p{L}0-9\-]+)+(?::\d+)?(?:[\/?#].*)?$/u', $url)) {
            return 'https://'.$url;
        }

        return $url;
    }

    public static function isValid(?string $url): bool
    {
        $url = (string) $url;

        if ($url === '' || mb_strlen($url) > self::MAX_LENGTH) {
            return false;
        }

        if (preg_match('/[\x00-\x20\x7F\\\\]/', $url)) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            // Interner Pfad, keine protokollrelative Adresse („//host“).
            return ! str_starts_with($url, '//');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return match ($scheme) {
            'http', 'https' => self::isValidWebUrl($url),
            'mailto' => (bool) filter_var(Str::before(Str::after($url, ':'), '?'), FILTER_VALIDATE_EMAIL),
            'tel' => (bool) preg_match('/^tel:\+?[0-9()\/.\-]{3,30}$/i', $url),
            default => false,
        };
    }

    private static function isValidWebUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return false;
        }

        // Keine Zugangsdaten in Links (Phishing-Muster „https://bank.de@böse.de“).
        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $host = $parts['host'];

        return strtolower($host) === 'localhost'
            || (preg_match('/^[\p{L}0-9.\-]+$/u', $host) === 1 && str_contains($host, '.'));
    }

    /** internal | external | mail | phone */
    public static function type(string $url): string
    {
        if (str_starts_with($url, '/')) {
            return 'internal';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($scheme === 'mailto') {
            return 'mail';
        }

        if ($scheme === 'tel') {
            return 'phone';
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        return $host !== '' && $host === $appHost ? 'internal' : 'external';
    }

    public static function absolute(string $url): string
    {
        return str_starts_with($url, '/') ? url($url) : $url;
    }

    /** Kurzform für die Anzeige: Host ohne „www.“, E-Mail-Adresse, Telefonnummer oder Pfad. */
    public static function display(string $url): string
    {
        return match (self::type($url)) {
            'mail', 'phone' => Str::before(Str::after($url, ':'), '?'),
            'internal' => 'Seite im ElternInfoBoard',
            default => (string) preg_replace('/^www\./i', '', (string) parse_url($url, PHP_URL_HOST)),
        };
    }
}
