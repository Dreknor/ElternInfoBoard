<?php

namespace App\Services;

use App\Model\Stundenplan\Schuljahr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StundenplanDataProvider
{
    /**
     * Get stundenplan data from database
     * Returns data in the same format as JSON import
     *
     * @return array|null
     */
    public function getStundenplanData(): ?array
    {
        return Cache::remember('stundenplan_data_from_db', 3600, function () {
            // Get active Schuljahr
            $schuljahr = Schuljahr::where('is_active', true)
                ->with(['zeitslots', 'klassen.eintraege.fach', 'klassen.eintraege.lehrer', 'klassen.eintraege.raeume', 'klassen.eintraege.zeitslot'])
                ->first();

            if (!$schuljahr) {
                return null;
            }

            // Build Basisdaten
            $basisdaten = [
                'DatumVon' => $schuljahr->datum_von->format('d.m.Y'),
                'DatumBis' => $schuljahr->datum_bis->format('d.m.Y'),
                'SwVon' => (string) $schuljahr->sw_von,
                'SwBis' => (string) $schuljahr->sw_bis,
                'TageProWoche' => (string) $schuljahr->tage_pro_woche,
                'Zeitstempel' => $schuljahr->zeitstempel ? $schuljahr->zeitstempel->format('d.m.Y, H:i') : now()->format('d.m.Y, H:i'),
            ];

            // Build Zeitslots
            $zeitslots = [];
            foreach ($schuljahr->zeitslots->sortBy('stunde') as $zeitslot) {
                $zeitslots[] = [
                    'Stunde' => (string) $zeitslot->stunde,
                    'ZeitVon' => $zeitslot->zeit_von instanceof \Carbon\Carbon
                        ? $zeitslot->zeit_von->format('H:i')
                        : $zeitslot->zeit_von,
                    'ZeitBis' => $zeitslot->zeit_bis instanceof \Carbon\Carbon
                        ? $zeitslot->zeit_bis->format('H:i')
                        : $zeitslot->zeit_bis,
                ];
            }

            // Build Klassen with Plan
            $klassen = [];
            foreach ($schuljahr->klassen->sortBy('kurzform') as $klasse) {
                $plan = [];

                foreach ($klasse->eintraege as $eintrag) {
                    $planEntry = [
                        'PlUn' => $eintrag->unterrichts_id,
                        'PlTg' => (string) $eintrag->wochentag,
                        'PlSt' => (string) $eintrag->zeitslot->stunde,
                        'PlFa' => $eintrag->fach ? $eintrag->fach->kuerzel : '',
                        'PlLe' => $eintrag->lehrer->pluck('kuerzel')->toArray(),
                        'PlRa' => $eintrag->raeume->pluck('kuerzel')->toArray(),
                        'PlKl' => $eintrag->klassen->pluck('kurzform')->toArray(),
                    ];

                    $plan[] = $planEntry;
                }

                $klassen[] = [
                    'Kurzform' => $klasse->kurzform,
                    'Plan' => $plan,
                ];
            }

            return [
                'Basisdaten' => $basisdaten,
                'Zeitslots' => $zeitslots,
                'Klassen' => $klassen,
            ];
        });
    }

    /**
     * Clear the cache
     */
    public function clearCache(): void
    {
        Cache::forget('stundenplan_data_from_db');
        Cache::forget('stundenplan_data'); // Old cache key
    }
}

