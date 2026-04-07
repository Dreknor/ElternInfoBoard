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

class StundenplanImportService
{
    /**
     * Import complete timetable from Indiware JSON export
     */
    public function importFromJson(array $jsonData): array
    {
        DB::beginTransaction();

        try {
            $stats = [
                'schuljahr' => 0,
                'klassen' => 0,
                'zeitslots' => 0,
                'lehrer' => 0,
                'raeume' => 0,
                'faecher' => 0,
                'eintraege' => 0,
                'errors' => [],
            ];

            // Extract main data
            $stundenplanData = $jsonData['Gesamtexport']['Stundenplan']['StundenplanKlassen'] ?? null;

            if (!$stundenplanData) {
                throw new \Exception('Ungültiges JSON-Format: StundenplanKlassen nicht gefunden');
            }

            // 1. Create Schuljahr
            $schuljahr = $this->createOrUpdateSchuljahr($stundenplanData);
            $stats['schuljahr'] = 1;

            // 2. Create Zeitslots (from first class)
            $firstClass = $stundenplanData['Klassen'][0] ?? null;
            if ($firstClass && isset($firstClass['Stunden'])) {
                $zeitslotCount = $this->createZeitslots($schuljahr, $firstClass['Stunden']);
                $stats['zeitslots'] = $zeitslotCount;
            }

            // 3. Process each class
            foreach ($stundenplanData['Klassen'] as $klasseData) {
                try {
                    $result = $this->processKlasse($schuljahr, $klasseData);
                    $stats['klassen']++;
                    $stats['lehrer'] += $result['lehrer'];
                    $stats['raeume'] += $result['raeume'];
                    $stats['faecher'] += $result['faecher'];
                    $stats['eintraege'] += $result['eintraege'];
                } catch (\Exception $e) {
                    $stats['errors'][] = "Klasse {$klasseData['Kurzform']}: {$e->getMessage()}";
                    Log::error("Import error for class {$klasseData['Kurzform']}", ['error' => $e->getMessage()]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'stats' => $stats,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stundenplan import failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $stats,
            ];
        }
    }

    /**
     * Create or update Schuljahr
     */
    private function createOrUpdateSchuljahr(array $data): Schuljahr
    {
        $basisdaten = $data['Basisdaten'];
        $kopf = $data['Kopf'];

        $datumVon = Carbon::createFromFormat('d.m.Y', $basisdaten['DatumVon']);
        $datumBis = Carbon::createFromFormat('d.m.Y', $basisdaten['DatumBis']);

        $name = $datumVon->year . '/' . $datumBis->year;

        return Schuljahr::updateOrCreate(
            ['name' => $name],
            [
                'datum_von' => $datumVon,
                'datum_bis' => $datumBis,
                'sw_von' => (int) $basisdaten['SwVon'],
                'sw_bis' => (int) $basisdaten['SwBis'],
                'tage_pro_woche' => (int) $basisdaten['TageProWoche'],
                'zeitstempel' => isset($kopf['Zeitstempel'])
                    ? Carbon::createFromFormat('d.m.Y, H:i', $kopf['Zeitstempel'])
                    : now(),
                'is_active' => true,
            ]
        );
    }

    /**
     * Create Zeitslots for Schuljahr
     */
    private function createZeitslots(Schuljahr $schuljahr, array $stunden): int
    {
        $count = 0;

        foreach ($stunden as $stunde) {
            Zeitslot::updateOrCreate(
                [
                    'schuljahr_id' => $schuljahr->id,
                    'stunde' => (int) $stunde['Stunde'],
                ],
                [
                    'zeit_von' => $stunde['ZeitVon'],
                    'zeit_bis' => $stunde['ZeitBis'],
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Process single class
     */
    private function processKlasse(Schuljahr $schuljahr, array $klasseData): array
    {
        $stats = [
            'lehrer' => 0,
            'raeume' => 0,
            'faecher' => 0,
            'eintraege' => 0,
        ];

        // Create Klasse
        $klasse = Klasse::updateOrCreate(
            [
                'schuljahr_id' => $schuljahr->id,
                'kurzform' => $klasseData['Kurzform'],
            ],
            [
                'name' => $klasseData['Kurzform'], // Could be extended with full name
            ]
        );

        // Process Plan entries
        if (isset($klasseData['Plan'])) {
            foreach ($klasseData['Plan'] as $planEntry) {
                $result = $this->processPlanEntry($schuljahr, $klasse, $planEntry);
                $stats['lehrer'] += $result['lehrer'];
                $stats['raeume'] += $result['raeume'];
                $stats['faecher'] += $result['faecher'];
                $stats['eintraege'] += $result['eintraege'];
            }
        }

        return $stats;
    }

    /**
     * Process single plan entry
     */
    private function processPlanEntry(Schuljahr $schuljahr, Klasse $klasse, array $entry): array
    {
        $stats = [
            'lehrer' => 0,
            'raeume' => 0,
            'faecher' => 0,
            'eintraege' => 0,
        ];

        // Get or create Fach
        $fach = null;
        if (isset($entry['PlFa'])) {
            $fach = Fach::firstOrCreate(
                ['kuerzel' => $entry['PlFa']],
                ['name' => $entry['PlFa']] // Could be extended with full name
            );
            $stats['faecher']++;
        }

        // Get Zeitslot
        $zeitslot = Zeitslot::where('schuljahr_id', $schuljahr->id)
            ->where('stunde', (int) $entry['PlSt'])
            ->first();

        if (!$zeitslot) {
            throw new \Exception("Zeitslot für Stunde {$entry['PlSt']} nicht gefunden");
        }

        // Create Eintrag
        $eintrag = Eintrag::create([
            'schuljahr_id' => $schuljahr->id,
            'zeitslot_id' => $zeitslot->id,
            'fach_id' => $fach ? $fach->id : null,
            'wochentag' => (int) $entry['PlTg'],
            'unterrichts_id' => $entry['PlUn'] ?? null,
        ]);
        $stats['eintraege']++;

        // Attach Klassen
        if (isset($entry['PlKl'])) {
            foreach ($entry['PlKl'] as $klasseKuerzel) {
                $k = Klasse::where('schuljahr_id', $schuljahr->id)
                    ->where('kurzform', $klasseKuerzel)
                    ->first();

                if ($k) {
                    $eintrag->klassen()->attach($k->id);
                }
            }
        }

        // Attach Lehrer
        if (isset($entry['PlLe'])) {
            foreach ($entry['PlLe'] as $lehrerKuerzel) {
                $lehrer = Lehrer::firstOrCreate(
                    ['kuerzel' => $lehrerKuerzel],
                    ['name' => null, 'vorname' => null]
                );
                $eintrag->lehrer()->attach($lehrer->id);
                $stats['lehrer']++;
            }
        }

        // Attach Räume
        if (isset($entry['PlRa'])) {
            foreach ($entry['PlRa'] as $raumKuerzel) {
                $raum = Raum::firstOrCreate(
                    ['kuerzel' => $raumKuerzel],
                    ['name' => null]
                );
                $eintrag->raeume()->attach($raum->id);
                $stats['raeume']++;
            }
        }

        return $stats;
    }
}

