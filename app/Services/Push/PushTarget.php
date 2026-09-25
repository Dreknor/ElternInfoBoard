<?php

namespace App\Services\Push;

/**
 * Leitet aus der (Web-)URL einer Benachrichtigung das Ziel in der App ab,
 * z. B. `/post/12` → ['type' => 'post', 'id' => 12].
 */
class PushTarget
{
    /** Bereiche, deren Texte Kinder- oder Gesundheitsdaten enthalten können. */
    private const SENSITIVE_TYPES = ['attendance', 'child', 'krankmeldung'];

    public static function fromUrl(?string $url): ?array
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $fragment = parse_url($url, PHP_URL_FRAGMENT);

        $patterns = [
            '#/(?:post|home|posts)/(\d+)#' => 'post',
            '#/messenger/conversation/(\d+)#' => 'conversation',
            '#/listen/(\d+)#' => 'liste',
            '#/termine/(\d+)#' => 'termin',
            '#/elternrat#' => 'elternrat',
            '#/(?:schickzeiten|anwesenheit|care)#' => 'attendance',
            '#/krankmeldung#' => 'krankmeldung',
            '#/pflichtstunden#' => 'pflichtstunden',
            '#/reinigung#' => 'reinigung',
            '#/arbeitsgemeinschaften#' => 'ags',
            '#/termine#' => 'termin',
            '#/listen#' => 'liste',
        ];

        foreach ($patterns as $regex => $type) {
            if (preg_match($regex, $path, $m)) {
                return ['type' => $type, 'id' => isset($m[1]) ? (int) $m[1] : null];
            }
        }

        if ($fragment && ctype_digit($fragment)) {
            return ['type' => 'post', 'id' => (int) $fragment];
        }

        return null;
    }

    public static function isSensitive(?array $target): bool
    {
        return $target !== null && in_array($target['type'], self::SENSITIVE_TYPES, true);
    }
}
