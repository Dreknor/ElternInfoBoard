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
     * Now supports multiple active Schuljahre with different Zeitraster
     *
     * @return array|null
     */
    public function getStundenplanData(): ?array
    {
        return Cache::remember('stundenplan_data_from_db', 3600, function () {
            // Get ALL active Schuljahre
            $schuljahre = Schuljahr::where('is_active', true)
                ->with(['zeitslots', 'klassen.eintraege.fach', 'klassen.eintraege.lehrer', 'klassen.eintraege.raeume', 'klassen.eintraege.zeitslot'])
                ->get();

            if ($schuljahre->isEmpty()) {
                return null;
            }

            // Use the first Schuljahr for Basisdaten (can be extended later)
            $mainSchuljahr = $schuljahre->first();

            // Build Basisdaten
            $basisdaten = [
                'DatumVon' => $mainSchuljahr->datum_von->format('d.m.Y'),
                'DatumBis' => $mainSchuljahr->datum_bis->format('d.m.Y'),
                'SwVon' => (string) $mainSchuljahr->sw_von,
                'SwBis' => (string) $mainSchuljahr->sw_bis,
                'TageProWoche' => (string) $mainSchuljahr->tage_pro_woche,
                'Zeitstempel' => $mainSchuljahr->zeitstempel ? $mainSchuljahr->zeitstempel->format('d.m.Y, H:i') : now()->format('d.m.Y, H:i'),
            ];

            // Build Zeitslots - combined from all Schuljahre (will be filtered per class later)
            $zeitslots = [];
            foreach ($schuljahre as $schuljahr) {
                foreach ($schuljahr->zeitslots->sortBy('stunde') as $zeitslot) {
                    $zeitslots[] = [
                        'Stunde' => (string) $zeitslot->stunde,
                        'ZeitVon' => $zeitslot->zeit_von instanceof \Carbon\Carbon
                            ? $zeitslot->zeit_von->format('H:i')
                            : $zeitslot->zeit_von,
                        'ZeitBis' => $zeitslot->zeit_bis instanceof \Carbon\Carbon
                            ? $zeitslot->zeit_bis->format('H:i')
                            : $zeitslot->zeit_bis,
                        'schuljahr_id' => $zeitslot->schuljahr_id, // NEW: Track which Schuljahr
                    ];
                }
            }

            // Build Klassen with Plan from ALL active Schuljahre
            $klassen = [];
            foreach ($schuljahre as $schuljahr) {
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
                        'schuljahr_id' => $klasse->schuljahr_id, // NEW: Track which Schuljahr
                        'schulform' => $schuljahr->schulform, // NEW: Track Schulform
                    ];
                }
            }

            return [
                'Basisdaten' => $basisdaten,
                'Zeitslots' => $zeitslots,
                'Klassen' => $klassen,
                'Schuljahre' => $schuljahre->map(function($sj) {
                    return [
                        'id' => $sj->id,
                        'name' => $sj->name,
                        'schulform' => $sj->schulform,
                        'beschreibung' => $sj->beschreibung,
                    ];
                })->toArray(),
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

