<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Migriert das E-Mail-Passwort in der settings-Tabelle von Klartext zu verschlüsselt.
 * Spatie Laravel Settings verwendet encrypt()/decrypt() für die encrypted()-Felder.
 */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('settings')
            ->where('group', 'email')
            ->where('name', 'mail_password')
            ->first();

        if ($row && $row->payload !== null) {
            $payload = json_decode($row->payload, true);

            // Nur verschlüsseln wenn noch nicht verschlüsselt (kein 'eyJ'-Prefix von Laravel Crypt)
            if (is_string($payload) && ! str_starts_with($payload, 'eyJ')) {
                $encrypted = Crypt::encryptString($payload);
                DB::table('settings')
                    ->where('group', 'email')
                    ->where('name', 'mail_password')
                    ->update(['payload' => json_encode($encrypted)]);
            }
        }
    }

    public function down(): void
    {

        $row = DB::table('settings')
            ->where('group', 'email')
            ->where('name', 'mail_password')
            ->first();

        if ($row && $row->payload !== null) {
            $payload = json_decode($row->payload, true);

            // Entschlüsseln wenn verschlüsselt
            if (is_string($payload) && str_starts_with($payload, 'eyJ')) {
                try {
                    $decrypted = Crypt::decryptString($payload);
                    DB::table('settings')
                        ->where('group', 'email')
                        ->where('name', 'mail_password')
                        ->update(['payload' => json_encode($decrypted)]);
                } catch (\Exception $e) {
                    // Kann nicht entschlüsselt werden – nichts tun
                }
            }
        }
    }
};
