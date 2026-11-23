<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Übernimmt die vorhandenen Tageszeilen einmalig in den An-/Abmeldeverlauf
 * (markiert als „importiert“). Der Befehl ist idempotent und kann bei Bedarf
 * erneut manuell ausgeführt werden: php artisan attendance:import-checkins
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Artisan::call('attendance:import-checkins');
            Log::info('Anwesenheitsverlauf importiert: '.trim(Artisan::output()));
        } catch (\Throwable $e) {
            Log::error('Import des Anwesenheitsverlaufs fehlgeschlagen: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        // Importierte Ereignisse werden mit der Tabelle entfernt (siehe create-Migration).
    }
};
