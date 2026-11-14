<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;

/**
 * Zentrale Erlaubnisliste für Datei-Uploads (Validierung).
 *
 * Ergänzt App\Support\UploadGuard: Der Guard sperrt aktive Inhalte beim Anlegen jedes Media-Eintrags
 * (Sperrliste) und härtet die Auslieferung; diese Regeln lassen schon bei der Validierung nur
 * bekannte Bild- und Dokumenttypen zu und melden den Fehler am Formularfeld.
 *
 * @see config/uploads.php
 */
class Uploads
{
    /**
     * Validierungsregeln für eine beliebige hochgeladene Datei (Bild oder Dokument).
     *
     * Die Endung muss auf der Erlaubnisliste stehen; zusätzlich darf der aus dem Inhalt ermittelte
     * Typ kein aktiver Inhalt sein (z. B. HTML, das als „.pdf“ hochgeladen wird).
     *
     * @return array<int, mixed>
     */
    public static function fileRules(?int $maxKilobytes = null): array
    {
        $rules = [
            'file',
            'extensions:'.implode(',', self::allowedExtensions()),
            function (string $attribute, mixed $value, Closure $fail): void {
                if ($value instanceof UploadedFile && self::isActiveContent($value->getMimeType())) {
                    $fail('Dieser Dateityp ist nicht erlaubt.');
                }
            },
        ];

        if ($maxKilobytes !== null) {
            $rules[] = 'max:'.$maxKilobytes;
        }

        return $rules;
    }

    /**
     * Validierungsregeln für ein hochgeladenes Rasterbild (kein SVG): Endung und Inhalt.
     *
     * @return array<int, string>
     */
    public static function imageRules(?int $maxKilobytes = null): array
    {
        $list = implode(',', (array) config('uploads.images', []));

        // "extensions" prüft die Endung des Dateinamens, "mimes" den aus dem Inhalt ermittelten Typ.
        $rules = ['file', 'extensions:'.$list, 'mimes:'.$list];

        if ($maxKilobytes !== null) {
            $rules[] = 'max:'.$maxKilobytes;
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    public static function allowedExtensions(): array
    {
        return array_values(array_unique(array_map('strtolower', array_merge(
            (array) config('uploads.images', []),
            (array) config('uploads.documents', []),
        ))));
    }

    /**
     * Inhalte, die ein Browser ausführen würde oder die auf dem Server ausführbar sind.
     */
    public static function isActiveContent(?string $mimeType): bool
    {
        $mime = strtolower(trim(explode(';', (string) $mimeType)[0]));

        if ($mime === '') {
            return false;
        }

        if (in_array($mime, UploadGuard::BLOCKED_MIME_TYPES, true)) {
            return true;
        }

        foreach (['html', 'xml', 'svg', 'javascript', 'ecmascript', 'php', 'x-sh', 'shellscript', 'x-msdownload', 'x-dosexec', 'x-executable', 'java-archive', 'x-python', 'x-perl'] as $needle) {
            // OOXML/ODF (docx, xlsx, odt …) enthalten „xml“ im MIME-Typ, sind aber Archive und kein aktiver Inhalt
            if (str_contains($mime, $needle) && ! str_contains($mime, 'officedocument') && ! str_contains($mime, 'opendocument')) {
                return true;
            }
        }

        return false;
    }
}
