<?php

namespace App\Services\Students;

use App\Model\User;
use Illuminate\Support\Facades\Log;

/**
 * Protokoll der Schülerkonten (docs/konzept-schuelerkonten.md §3, §10, Paket S-11).
 *
 * Eintrag im vorhandenen Log-System (Kategorie „Schülerkonten“ in /logs über das Stichwort
 * „Schülerkonto“). Gespeichert werden nur Konto-ID und ausführendes Konto, keine Namen.
 */
final class StudentAccountLog
{
    public static function info(string $message, User|int|null $student, array $context = []): void
    {
        self::write('info', $message, $student, $context);
    }

    public static function warning(string $message, User|int|null $student, array $context = []): void
    {
        self::write('warning', $message, $student, $context);
    }

    private static function write(string $level, string $message, User|int|null $student, array $context): void
    {
        $context = array_merge([
            'schuelerkonto_id' => $student instanceof User ? $student->id : $student,
            'durch' => auth()->id(),
        ], $context);

        Log::{$level}('[Schülerkonto] '.$message, $context);
    }
}
