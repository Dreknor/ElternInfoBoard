<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Repariert das gespeicherte Mail-Passwort (Settings `email.mail_password`).
 *
 * Spatie Settings erwartet einen mit Crypt::encrypt() verschlüsselten Wert oder null.
 * Ältere Migrationen konnten andere Zustände hinterlassen:
 *  - Zahl (Platzhalter `now()->timestamp`, wenn MAIL_PASSWORD leer/null war) → null
 *  - Klartext (nicht verschlüsselt)                                         → verschlüsselt
 *  - Crypt::encryptString() ohne Serialisierung                            → neu verschlüsselt
 *  - doppelt serialisiert (2026_03_10 nach bereits korrekter Verschlüsselung) → bereinigt
 * Korrekte Werte bleiben unverändert. Mit anderem APP_KEY verschlüsselte Werte lassen
 * sich nicht reparieren; dann muss das Passwort in den Einstellungen neu gespeichert werden.
 */
return new class extends Migration
{
    private const UNCHANGED = "\0unchanged";

    public function up(): void
    {
        $row = DB::table('settings')
            ->where('group', 'email')
            ->where('name', 'mail_password')
            ->first();

        if (! $row) {
            return;
        }

        $value = $this->plaintext(json_decode((string) $row->payload, true));
        if ($value === self::UNCHANGED) {
            return;
        }

        DB::table('settings')
            ->where('group', 'email')
            ->where('name', 'mail_password')
            ->update(['payload' => json_encode($value === null ? null : Crypt::encrypt($value))]);

        Log::info('Mail-Passwort in den Einstellungen repariert.');
    }

    public function down(): void
    {
        // Keine Rückabwandlung: der reparierte Zustand ist der erwartete.
    }

    /**
     * Klartext-Passwort, null (kein Passwort) oder UNCHANGED (bereits korrekt / nicht reparierbar).
     */
    private function plaintext(mixed $payload): ?string
    {
        if ($payload === null) {
            return self::UNCHANGED;
        }

        // Platzhalter (Zeitstempel) oder sonstiger Nicht-Text: nie ein echtes Passwort
        if (! is_string($payload)) {
            return null;
        }

        if (! str_starts_with($payload, 'eyJ')) {
            return in_array(strtolower(trim($payload)), ['', 'null'], true) ? null : $payload;
        }

        try {
            $decrypted = Crypt::decryptString($payload);
        } catch (DecryptException) {
            Log::warning('Mail-Passwort lässt sich nicht entschlüsseln (anderer APP_KEY?). Bitte in den Einstellungen neu speichern.');

            return self::UNCHANGED;
        }

        $once = $this->unserialized($decrypted);

        // encryptString()-Format (kein serialisierter Wert)
        if (! $once['ok']) {
            return $decrypted;
        }

        // Doppelt serialisiert → einmal mehr auspacken
        if (is_string($once['value'])) {
            $twice = $this->unserialized($once['value']);
            if ($twice['ok'] && (is_string($twice['value']) || $twice['value'] === null)) {
                return $twice['value'];
            }

            return self::UNCHANGED; // korrekt mit encrypt() verschlüsselt
        }

        // encrypt(null) o. ä. ist gültig; andere Typen (z. B. Zahl) sind Platzhalter
        return $once['value'] === null ? self::UNCHANGED : null;
    }

    /**
     * @return array{ok: bool, value: mixed}
     */
    private function unserialized(string $value): array
    {
        if ($value === 'b:0;') {
            return ['ok' => true, 'value' => false];
        }

        $result = @unserialize($value, ['allowed_classes' => false]);

        return ['ok' => $result !== false, 'value' => $result];
    }
};
