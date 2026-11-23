<?php

use App\Settings\PflichtstundenSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Einmalige Nacherfassung der automatischen Pflichtstunden, die wegen des Fehlers in
 * ListenService::reserveTermin (Buchung ohne Model-Events, seit 25.09.2026) nicht
 * angelegt wurden. Läuft beim Update über „migrate --force“ genau einmal je Installation.
 *
 * Doppelte Einträge sind ausgeschlossen: pflichtstunden:listen-nacherfassen legt nur für
 * Termine an, zu denen keine Pflichtstunde existiert (auch keine abgelehnte oder gelöschte),
 * und prüft das je Termin unter Zeilensperre. Ein erneuter Aufruf legt nichts doppelt an.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            if (! app(PflichtstundenSetting::class)->listen_autocreate) {
                Log::info('Nacherfassung Listen-Pflichtstunden übersprungen: listen_autocreate ist deaktiviert.');

                return;
            }

            Artisan::call('pflichtstunden:listen-nacherfassen', ['--seit' => '2026-09-25']);
            Log::info('Nacherfassung Listen-Pflichtstunden', ['ausgabe' => trim(Artisan::output())]);
        } catch (\Throwable $e) {
            // Update nicht blockieren – kann jederzeit manuell nachgeholt werden:
            // php artisan pflichtstunden:listen-nacherfassen
            Log::error('Nacherfassung Listen-Pflichtstunden fehlgeschlagen: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        // Nacherfasste Pflichtstunden bleiben bestehen (ggf. bereits genehmigt).
    }
};
