<?php

namespace App\Support;

/**
 * Erzeugt Start-Kennwörter für den Nutzer-Import (PDF-/E-Mail-Versand).
 *
 * Es werden bewusst KEINE Sonderzeichen sowie keine optisch leicht
 * verwechselbaren Zeichen (z.B. "O"/"0", "l"/"1"/"I") verwendet. Genau diese
 * Verwechslungen beim manuellen Abtippen eines aus der PDF entnommenen
 * Kennworts haben dazu geführt, dass Nutzer beim Login abgelehnt wurden,
 * obwohl das hinterlegte Kennwort korrekt war. Das erzeugte Kennwort besteht
 * daher ausschließlich aus eindeutig unterscheidbaren Groß-/Kleinbuchstaben
 * und Ziffern und ist dadurch für den Nutzer einfach korrekt zu übernehmen.
 */
class PasswordGenerator
{
    /** Ohne I, O (optisch zu nah an l/1 bzw. 0). */
    private const LETTERS_UPPER = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    /** Ohne l, o (optisch zu nah an 1/I bzw. 0). */
    private const LETTERS_LOWER = 'abcdefghijkmnpqrstuvwxyz';

    /** Ohne 0, 1 (optisch zu nah an O bzw. I/l). */
    private const DIGITS = '23456789';

    public static function generate(int $length = 12): string
    {
        $pools = [self::LETTERS_UPPER, self::LETTERS_LOWER, self::DIGITS];
        $all = implode('', $pools);

        // Mindestens ein Zeichen aus jedem Zeichensatz, damit das Kennwort
        // immer Groß-/Kleinbuchstaben und Ziffern enthält.
        $password = array_map(
            fn (string $pool) => $pool[random_int(0, strlen($pool) - 1)],
            $pools
        );

        for ($i = count($password); $i < $length; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($password);

        return implode('', $password);
    }
}
