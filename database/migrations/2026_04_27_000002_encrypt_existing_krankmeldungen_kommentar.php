<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Verschlüsselt bestehende Klartext-Werte der Spalte `krankmeldungen.kommentar`
     * mit dem App-Key (AES-256-CBC). Idempotent: bereits verschlüsselte Werte
     * werden via Try/Catch erkannt und übersprungen.
     *
     * WICHTIG: Vor dem Roll-out unbedingt ein Datenbank-Backup erstellen!
     */
    public function up(): void
    {
        $migrated = 0;
        $skipped  = 0;

        DB::table('krankmeldungen')
            ->select(['id', 'kommentar'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$migrated, &$skipped) {
                foreach ($rows as $row) {
                    $value = $row->kommentar;

                    if ($value === null || $value === '') {
                        $skipped++;
                        continue;
                    }

                    // Bereits verschlüsselt? -> dann ist das Decrypt erfolgreich.
                    try {
                        Crypt::decryptString($value);
                        $skipped++;
                        continue;
                    } catch (\Throwable $e) {
                        // -> Klartext, muss verschlüsselt werden.
                    }

                    DB::table('krankmeldungen')
                        ->where('id', $row->id)
                        ->update(['kommentar' => Crypt::encryptString($value)]);

                    $migrated++;
                }
            });

        Log::info('Krankmeldungen.kommentar Verschlüsselung abgeschlossen', [
            'verschluesselt' => $migrated,
            'uebersprungen'  => $skipped,
        ]);
    }

    public function down(): void
    {
        // Klartext-Roll-back wird bewusst nicht angeboten (Datenschutz).
    }
};

