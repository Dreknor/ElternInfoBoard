<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Korrigiert die Verschlüsselung des Mail-Passworts in der settings-Tabelle.
 *
 * Das vorherige Migration hat Crypt::encryptString() verwendet (ohne PHP-Serialisierung).
 * Spatie Laravel Settings erwartet jedoch Crypt::encrypt() (mit serialize=true),
 * da es intern unserialize() auf den entschlüsselten Wert aufruft.
 *
 * Diese Migration:
 * 1. Entschlüsselt den Wert mit decryptString() (kein unserialize)
 * 2. Verschlüsselt ihn erneut mit encrypt() (mit serialize=true, Spatie-kompatibel)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $row = DB::table('settings')
            ->where('group', 'email')
            ->where('name', 'mail_password')
            ->first();

        if ($row && $row->payload !== null) {
            $payload = json_decode($row->payload, true);

            if (is_string($payload) && str_starts_with($payload, 'eyJ')) {
                try {
                    // Mit decryptString entschlüsseln (kein unserialize – wie von encryptString erwartet)
                    $plaintext = Crypt::decryptString($payload);

                    // Mit encrypt() neu verschlüsseln (mit Serialisierung – Spatie-kompatibel)
                    $reEncrypted = Crypt::encrypt($plaintext);

                    DB::table('settings')
                        ->where('group', 'email')
                        ->where('name', 'mail_password')
                        ->update(['payload' => json_encode($reEncrypted)]);

                    Log::info('Mail-Passwort-Verschlüsselung erfolgreich korrigiert.');
                } catch (\Exception $e) {
                    Log::error('Fehler beim Korrigieren der Mail-Passwort-Verschlüsselung: ' . $e->getMessage());
                    throw $e;
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $row = DB::table('settings')
            ->where('group', 'email')
            ->where('name', 'mail_password')
            ->first();

        if ($row && $row->payload !== null) {
            $payload = json_decode($row->payload, true);

            if (is_string($payload) && str_starts_with($payload, 'eyJ')) {
                try {
                    // Mit decrypt() entschlüsseln (unserialize – wie von encrypt erwartet)
                    $plaintext = Crypt::decrypt($payload);

                    // Mit encryptString() neu verschlüsseln (ohne Serialisierung – wie vorher)
                    $reEncrypted = Crypt::encryptString($plaintext);

                    DB::table('settings')
                        ->where('group', 'email')
                        ->where('name', 'mail_password')
                        ->update(['payload' => json_encode($reEncrypted)]);
                } catch (\Exception $e) {
                    Log::error('Fehler beim Zurücksetzen der Mail-Passwort-Verschlüsselung: ' . $e->getMessage());
                }
            }
        }
    }
};
