<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gefährliche Dateitypen für alle Uploads über die Medienbibliothek sperren.
 *
 * Mediendateien liegen auf der Disk `public` und sind damit unter /storage/… direkt vom Webserver
 * abrufbar. HTML-, SVG- oder XML-Dateien würden dort als Seite der Schule ausgeführt (Stored XSS),
 * PHP-Dateien je nach Webserver sogar als Programmcode. Die Prüfung greift beim Anlegen jedes
 * Media-Eintrags, damit kein Upload-Weg (Web, App-API, Messenger, Krankmeldung …) vergessen wird.
 */
class UploadGuard
{
    /** Gesperrte Dateiendungen. */
    public const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar', 'inc',
        'html', 'htm', 'xhtml', 'xht', 'shtml', 'shtm', 'mht', 'mhtml', 'hta',
        'svg', 'svgz', 'xml', 'xsl', 'xslt',
        'js', 'mjs', 'cjs', 'swf', 'jsp', 'asp', 'aspx', 'cgi', 'pl', 'py', 'sh', 'htaccess', 'htpasswd',
    ];

    /** Ausführbare Endungen sind auch als Zwischenendung gesperrt (Apache wertet „datei.php.jpg“ als PHP aus). */
    public const EXECUTABLE_EXTENSIONS = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'shtml', 'cgi'];

    public const BLOCKED_MIME_TYPES = [
        'text/html', 'application/xhtml+xml', 'image/svg+xml', 'text/xml', 'application/xml',
        'text/javascript', 'application/javascript', 'application/x-javascript',
        'application/x-httpd-php', 'application/x-php', 'text/x-php', 'application/x-shockwave-flash',
    ];

    public static function isBlocked(?string $fileName, ?string $mimeType = null): bool
    {
        $name = strtolower(trim((string) $fileName));
        $segments = array_slice(explode('.', $name), 1);
        if (str_starts_with($name, '.ht')
            || in_array(end($segments) ?: '', self::BLOCKED_EXTENSIONS, true)
            || array_intersect($segments, self::EXECUTABLE_EXTENSIONS) !== []) {
            return true;
        }

        $mime = strtolower(trim(explode(';', (string) $mimeType)[0]));

        return $mime !== '' && in_array($mime, self::BLOCKED_MIME_TYPES, true);
    }

    /**
     * @throws ValidationException
     */
    public static function assertAllowed(Media $media): void
    {
        if (self::isBlocked($media->file_name, $media->mime_type)) {
            throw ValidationException::withMessages([
                'files' => 'Dieser Dateityp ist aus Sicherheitsgründen nicht erlaubt.',
            ]);
        }
    }

    /**
     * Auslieferung einer Mediendatei absichern: Browser darf den Typ nicht erraten, und selbst
     * ältere HTML-/SVG-Dateien laufen in einer Sandbox ohne Skripte. Nur Rasterbilder inline.
     */
    public static function harden(Response $response, Media $media, bool $inline = true): Response
    {
        $mime = strtolower((string) $media->mime_type);
        $raster = str_starts_with($mime, 'image/') && ! str_contains($mime, 'svg');
        $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $media->file_name) ?: 'datei';

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox");
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            $inline && $raster ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            (string) $media->file_name ?: 'datei',
            $fallback,
        ));

        return $response;
    }
}
