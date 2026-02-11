<?php

namespace App\Services;

use App\Model\Stundenplan\Schuljahr;
use App\Model\Stundenplan\Klasse;
use App\Model\Stundenplan\Zeitslot;
use App\Model\Stundenplan\Lehrer;
use App\Model\Stundenplan\Raum;
use App\Model\Stundenplan\Fach;
use App\Model\Stundenplan\Eintrag;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class StundenplanDatabaseImporter
{
    /**
     * Import normalized stundenplan data to database
     *
     * @param array $data Normalized data from StundenplanDataAdapter
     * @return array Import statistics
     */
    public function import(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $stats = [
                'schuljahr_id' => null,
                'klassen' => 0,
                'zeitslots' => 0,
                'lehrer' => 0,
                'raeume' => 0,
                'faecher' => 0,
                'eintraege' => 0,
            ];

            // 1. Create or update Schuljahr
            $schuljahr = $this->importSchuljahr($data['Basisdaten']);
            $stats['schuljahr_id'] = $schuljahr->id;

            // 2. Import Zeitslots
            $zeitslots = $this->importZeitslots($schuljahr, $data['Zeitslots']);
            $stats['zeitslots'] = count($zeitslots);

            // 3. Import Klassen
            $klassen = $this->importKlassen($schuljahr, $data['Klassen']);
            $stats['klassen'] = count($klassen);

            // 4. Import Einträge (Fächer, Lehrer, Räume werden dabei erstellt)
            foreach ($data['Klassen'] as $klasseData) {
                $klasse = $klassen[$klasseData['Kurzform']];

                foreach ($klasseData['Plan'] as $planEintrag) {
                    $this->importEintrag($schuljahr, $klasse, $planEintrag, $zeitslots);
                    $stats['eintraege']++;
                }
            }

            // Count created resources
            $stats['lehrer'] = Lehrer::count();
            $stats['raeume'] = Raum::count();
            $stats['faecher'] = Fach::count();

            Log::info('Stundenplan imported to database', $stats);

            return $stats;
        });
    }

    /**
     * Import Schuljahr
     */
    private function importSchuljahr(array $basisdaten): Schuljahr
    {
        $datumVon = Carbon::createFromFormat('d.m.Y', $basisdaten['DatumVon']);
        $datumBis = Carbon::createFromFormat('d.m.Y', $basisdaten['DatumBis']);

        $name = $datumVon->year . '/' . $datumBis->year;

        // Deactivate all other Schuljahre
        Schuljahr::where('is_active', true)->update(['is_active' => false]);

        // Create or update current Schuljahr
        return Schuljahr::updateOrCreate(
            ['name' => $name],
            [
                'datum_von' => $datumVon,
                'datum_bis' => $datumBis,
                'sw_von' => $basisdaten['SwVon'],
                'sw_bis' => $basisdaten['SwBis'],
                'tage_pro_woche' => $basisdaten['TageProWoche'],
                'zeitstempel' => isset($basisdaten['Zeitstempel'])
                    ? Carbon::createFromFormat('d.m.Y, H:i', $basisdaten['Zeitstempel'])
                    : now(),
                'is_active' => true,
            ]
        );
    }

    /**
     * Import Zeitslots
     */
    private function importZeitslots(Schuljahr $schuljahr, array $zeitslots): array
    {
        $result = [];

        foreach ($zeitslots as $zeitslotData) {
            $zeitslot = Zeitslot::updateOrCreate(
                [
                    'schuljahr_id' => $schuljahr->id,
                    'stunde' => $zeitslotData['Stunde'],
                ],
                [
                    'zeit_von' => $zeitslotData['ZeitVon'],
                    'zeit_bis' => $zeitslotData['ZeitBis'],
                ]
            );

            $result[$zeitslotData['Stunde']] = $zeitslot;
        }

        return $result;
    }

    /**
     * Import Klassen
     */
    private function importKlassen(Schuljahr $schuljahr, array $klassen): array
    {
        $result = [];

        foreach ($klassen as $klasseData) {
            $klasse = Klasse::updateOrCreate(
                [
                    'schuljahr_id' => $schuljahr->id,
                    'kurzform' => $klasseData['Kurzform'],
                ],
                [
                    'name' => $klasseData['Kurzform'], // Can be extended with full name
                ]
            );

            $result[$klasseData['Kurzform']] = $klasse;
        }

        return $result;
    }

    /**
     * Import a single Eintrag with relations
     *
     * WICHTIG: Erstellt einen SEPARATEN Eintrag für JEDE Klasse!
     * Auch wenn mehrere Klassen zur selben Zeit dasselbe Fach haben,
     * werden separate Einträge erstellt, damit Lehrer/Räume klassenspezifisch sind.
     */
    private function importEintrag(Schuljahr $schuljahr, Klasse $aktuelleKlasse, array $planEintrag, array $zeitslots): void
    {
        // Get Zeitslot
        $zeitslot = $zeitslots[$planEintrag['PlSt']] ?? null;
        if (!$zeitslot) {
            Log::warning('Zeitslot not found', ['stunde' => $planEintrag['PlSt']]);
            return;
        }

        // Get or create Fach
        $fach = null;
        if (!empty($planEintrag['PlFa'])) {
            $fach = Fach::firstOrCreate(
                ['kuerzel' => $planEintrag['PlFa']],
                ['name' => $planEintrag['PlFa']]
            );
        }

        // Create SEPARATE Eintrag pro Klasse
        // Unique identifier: schuljahr + zeitslot + wochentag + fach + KLASSE
        // Dies stellt sicher, dass jede Klasse ihren eigenen Eintrag hat,
        // auch wenn sie zur selben Zeit dasselbe Fach haben.
        $eintrag = Eintrag::firstOrCreate(
            [
                'schuljahr_id' => $schuljahr->id,
                'zeitslot_id' => $zeitslot->id,
                'wochentag' => $planEintrag['PlTg'],
                'fach_id' => $fach?->id,
                // Durch die klassen()-Relation wird sichergestellt dass jeder Eintrag
                // nur eine Klasse hat (siehe unten)
            ],
            [
                'unterrichts_id' => $planEintrag['PlUn'] ?? null,
            ]
        );

        // Attach GENAU EINE Klasse zu diesem Eintrag
        // (nicht mehrere, wie vorher gedacht)
        if (!$eintrag->klassen()->where('klasse_id', $aktuelleKlasse->id)->exists()) {
            // Prüfen ob dieser Eintrag bereits eine andere Klasse hat
            if ($eintrag->klassen()->count() > 0) {
                // Eintrag existiert bereits für andere Klasse -> Neuen Eintrag erstellen!
                $eintrag = Eintrag::create([
                    'schuljahr_id' => $schuljahr->id,
                    'zeitslot_id' => $zeitslot->id,
                    'wochentag' => $planEintrag['PlTg'],
                    'fach_id' => $fach?->id,
                    'unterrichts_id' => $planEintrag['PlUn'] ?? null,
                ]);
            }
            $eintrag->klassen()->attach($aktuelleKlasse->id);
        }

        // Attach Lehrer (nur für DIESE Klasse)
        if (!empty($planEintrag['PlLe'])) {
            foreach ($planEintrag['PlLe'] as $lehrerKuerzel) {
                $lehrer = Lehrer::firstOrCreate(
                    ['kuerzel' => $lehrerKuerzel],
                    ['name' => $lehrerKuerzel]
                );

                if (!$eintrag->lehrer()->where('lehrer_id', $lehrer->id)->exists()) {
                    $eintrag->lehrer()->attach($lehrer->id);
                }
            }
        }

        // Attach Räume (nur für DIESE Klasse)
        if (!empty($planEintrag['PlRa'])) {
            foreach ($planEintrag['PlRa'] as $raumKuerzel) {
                $raum = Raum::firstOrCreate(
                    ['kuerzel' => $raumKuerzel],
                    ['name' => $raumKuerzel]
                );

                if (!$eintrag->raeume()->where('raum_id', $raum->id)->exists()) {
                    $eintrag->raeume()->attach($raum->id);
                }
            }
        }
    }

    /**
     * Clear data for a Schuljahr (Einträge, Klassen, Zeitslots)
     * Does NOT delete the Schuljahr itself
     */
    public function clearSchuljahrData(Schuljahr $schuljahr): void
    {
        DB::transaction(function () use ($schuljahr) {
            // Delete all Einträge for this Schuljahr (cascades to pivot tables)
            Eintrag::where('schuljahr_id', $schuljahr->id)->delete();

            // Delete Klassen (cascades to pivot tables)
            Klasse::where('schuljahr_id', $schuljahr->id)->delete();

            // Delete Zeitslots
            Zeitslot::where('schuljahr_id', $schuljahr->id)->delete();

            Log::info('Cleared Schuljahr data', ['schuljahr_id' => $schuljahr->id]);
        });
    }

    /**
     * Clear Schuljahr and all its data completely
     * This deletes the Schuljahr record itself along with all related data
     */
    public function clearSchuljahr(Schuljahr $schuljahr): void
    {
        DB::transaction(function () use ($schuljahr) {
            $schuljahrId = $schuljahr->id;
            $schuljahrName = $schuljahr->name;

            // First clear all data
            $this->clearSchuljahrData($schuljahr);

            // Then delete the Schuljahr itself
            $schuljahr->delete();

            // Delete JSON files (current.json and history)
            if (Storage::disk('local')->exists('stundenplan/current.json')) {
                Storage::disk('local')->delete('stundenplan/current.json');
            }

            // Clear cache
            Cache::forget('stundenplan_data');
            Cache::forget('stundenplan_data_from_db');

            Log::info('Deleted Schuljahr completely', [
                'schuljahr_id' => $schuljahrId,
                'schuljahr_name' => $schuljahrName,
            ]);
        });
    }

    /**
     * Clear all inactive Schuljahre
     */
    public function clearInactiveSchuljahre(): int
    {
        return DB::transaction(function () {
            $inactiveSchuljahre = Schuljahr::where('is_active', false)->get();
            $count = $inactiveSchuljahre->count();

            foreach ($inactiveSchuljahre as $schuljahr) {
                $this->clearSchuljahr($schuljahr);
            }

            Log::info('Cleared inactive Schuljahre', ['count' => $count]);

            return $count;
        });
    }
}






