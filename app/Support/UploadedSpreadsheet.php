<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Hochgeladene Tabellen vor dem Einlesen mit Laravel Excel in den Storage verschieben.
 *
 * Laravel Excel öffnet Uploads über getRealPath(). Liegt die PHP-Temp-Datei in einem
 * Ordner ohne Leserecht (z. B. C:\Windows\Temp unter Laragon), liefert realpath() false
 * und der Import bricht mit „Path must not be empty“ ab. rename() funktioniert dagegen.
 */
class UploadedSpreadsheet
{
    /**
     * Führt $callback mit dem lokalen Pfad der Datei aus und löscht sie danach wieder.
     *
     * @template T
     *
     * @param  callable(string): T  $callback
     * @return T
     */
    public static function with(UploadedFile $file, callable $callback): mixed
    {
        $dir = storage_path('app/imports/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $extension = Str::lower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?? 'xlsx'));
        $path = $file->move($dir, 'upload_'.Str::random(24).'.'.$extension)->getPathname();

        try {
            return $callback($path);
        } finally {
            @unlink($path);
        }
    }
}
