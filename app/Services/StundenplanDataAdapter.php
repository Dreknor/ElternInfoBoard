<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class StundenplanDataAdapter
{
    /**
     * Normalize imported data to internal format
     * Supports:
     * - Direct format (Basisdaten, Zeitslots, Klassen)
     * - Indiware Export format (Gesamtexport.Stundenplan.StundenplanKlassen)
     * - Indiware Gesamtexport Version 1.1 (Gesamtexport.Stammdaten + Gesamtexport.Stundenplan)
     */
    public static function normalize(array $data): array
    {
        // Check if it's already in the correct format
        if (isset($data['Basisdaten']) && isset($data['Zeitslots']) && isset($data['Klassen'])) {
            return $data;
        }

        // Check if it's Indiware Gesamtexport Version 1.1 (new format)
        if (isset($data['Gesamtexport']['Informationen']['Version']) &&
            $data['Gesamtexport']['Informationen']['Version'] === '1.1' &&
            isset($data['Gesamtexport']['Stammdaten'])) {
            return self::convertFromIndiwareGesamtexportV11($data['Gesamtexport']);
        }

        // Check if it's older Indiware Gesamtexport format
        if (isset($data['Gesamtexport']['Stundenplan']['StundenplanKlassen'])) {
            return self::convertFromIndiwareExport($data['Gesamtexport']['Stundenplan']['StundenplanKlassen']);
        }

        throw new \Exception('Unbekanntes Datenformat. Erwartete Struktur: Basisdaten, Zeitslots, Klassen oder Indiware Gesamtexport');
    }

    /**
     * Convert from Indiware Gesamtexport Version 1.1 to internal format
     * Structure:
     * - Stammdaten.Grunddaten (Schuljahr, Beginn, Ende, Zeiten, Schulwochen)
     * - Stammdaten.Unterricht (Nummer, Fach, Lehrer, Klassen, Stunden)
     * - Stammdaten.Stundenplan.PlanSp (Nummer, Tag, Stunde, Raeume, Woche)
     */
    private static function convertFromIndiwareGesamtexportV11(array $gesamtexport): array
    {
        $grunddaten = $gesamtexport['Stammdaten']['Grunddaten'] ?? [];
        $unterricht = $gesamtexport['Stammdaten']['Unterricht'] ?? [];
        $planSp = $gesamtexport['Stammdaten']['Stundenplan']['PlanSp'] ?? [];

        // Build Basisdaten
        $beginn = $grunddaten['Beginn'] ?? '01.08.2025';
        $ende = $grunddaten['Ende'] ?? '31.07.2026';

        // Extract week numbers
        $schulwochen = $grunddaten['Schulwochen'] ?? [];
        $swVon = !empty($schulwochen) ? $schulwochen[0]['Nr'] : 1;
        $swBis = !empty($schulwochen) ? $schulwochen[count($schulwochen) - 1]['Nr'] : 52;

        $result = [
            'Basisdaten' => [
                'DatumVon' => $beginn,
                'DatumBis' => $ende,
                'SwVon' => (string) $swVon,
                'SwBis' => (string) $swBis,
                'TageProWoche' => '5',
                'Zeitstempel' => now()->format('d.m.Y, H:i'),
            ],
            'Zeitslots' => [],
            'Klassen' => [],
        ];

        // Build Zeitslots from Zeiten
        $dauerEinzelstunde = (int) ($grunddaten['DauerEinzelstunde'] ?? 45);
        foreach ($grunddaten['Zeiten'] ?? [] as $zeit) {
            // Calculate end time based on DauerEinzelstunde
            $zeitVon = $zeit['Zeit'];
            $zeitVonTime = \Carbon\Carbon::createFromFormat('H:i', $zeitVon);
            $zeitBis = $zeitVonTime->copy()->addMinutes($dauerEinzelstunde)->format('H:i');

            $result['Zeitslots'][] = [
                'Stunde' => (string) $zeit['Stunde'],
                'ZeitVon' => $zeitVon,
                'ZeitBis' => $zeitBis,
            ];
        }

        // Index Unterricht by Nummer
        $unterrichtsMap = [];
        foreach ($unterricht as $u) {
            $unterrichtsMap[$u['Nummer']] = $u;
        }

        // Group PlanSp by Nummer
        $planSpByNummer = [];
        foreach ($planSp as $element) {
            $nummer = $element['Nummer'];
            if (!isset($planSpByNummer[$nummer])) {
                $planSpByNummer[$nummer] = [];
            }
            $planSpByNummer[$nummer][] = $element;
        }

        // Build Plan entries: Map Nummer → Tag/Stunde/Raum from PlanSp
        $planEntries = [];
        foreach ($planSpByNummer as $nummer => $elemente) {
            if (!isset($unterrichtsMap[$nummer])) {
                continue; // Skip if no matching Unterricht
            }

            $unterrichtData = $unterrichtsMap[$nummer];
            $fach = $unterrichtData['Fach'] ?? '';
            $lehrer = $unterrichtData['Lehrer'] ?? [];
            $klassen = $unterrichtData['Klassen'] ?? [];

            // Each element is a scheduled slot
            foreach ($elemente as $element) {
                $tag = $element['Tag'];
                $stunde = $element['Stunde'];
                $raeume = $element['Raeume'] ?? [];
                $woche = $element['Woche'] ?? 0; // 0 = beide, 1 = Woche 1, 2 = Woche 2

                // Skip if woche-specific planning (we use woche 0 = both weeks for now)
                // TODO: Future enhancement for A/B weeks
                if ($woche != 0) {
                    continue; // Skip A/B-Wochen specific entries for now
                }

                $planEntry = [
                    'PlUn' => (string) $nummer,
                    'PlTg' => (string) $tag,
                    'PlSt' => (string) $stunde,
                    'PlFa' => $fach,
                    'PlLe' => is_array($lehrer) ? $lehrer : [$lehrer],
                    'PlRa' => is_array($raeume) ? $raeume : ($raeume ? [$raeume] : []),
                    'PlKl' => is_array($klassen) ? $klassen : [$klassen],
                ];

                $planEntries[] = $planEntry;
            }
        }

        // Collect all unique class names
        $allKlassen = [];
        foreach ($planEntries as $entry) {
            foreach ($entry['PlKl'] as $klasse) {
                $allKlassen[$klasse] = true;
            }
        }

        // Build Klassen entries
        foreach (array_keys($allKlassen) as $klassenName) {
            $klassePlan = [];

            // Find all plan entries for this class
            foreach ($planEntries as $planEntry) {
                if (in_array($klassenName, $planEntry['PlKl'])) {
                    $klassePlan[] = $planEntry;
                }
            }

            $result['Klassen'][] = [
                'Kurzform' => $klassenName,
                'Plan' => $klassePlan,
            ];
        }

        Log::info('Converted Indiware Gesamtexport V1.1 to internal format', [
            'klassen_count' => count($result['Klassen']),
            'zeitslots_count' => count($result['Zeitslots']),
            'plan_entries' => count($planEntries),
            'unterricht' => count($unterricht),
            'planSp' => count($planSp),
        ]);

        return $result;
    }

    /**
     * Convert from Indiware Gesamtexport to internal format (older format)
     */
    private static function convertFromIndiwareExport(array $stundenplanKlassen): array
    {
        $result = [
            'Basisdaten' => $stundenplanKlassen['Basisdaten'] ?? [],
            'Zeitslots' => [],
            'Klassen' => [],
        ];

        // Add timestamp from Kopf if available
        if (isset($stundenplanKlassen['Kopf']['Zeitstempel'])) {
            $result['Basisdaten']['Zeitstempel'] = $stundenplanKlassen['Kopf']['Zeitstempel'];
        }

        // Process Klassen
        foreach ($stundenplanKlassen['Klassen'] ?? [] as $klasse) {
            // Extract Zeitslots from first class (they should be the same for all)
            if (empty($result['Zeitslots']) && isset($klasse['Stunden'])) {
                foreach ($klasse['Stunden'] as $stunde) {
                    $result['Zeitslots'][] = [
                        'Stunde' => $stunde['Stunde'],
                        'ZeitVon' => $stunde['ZeitVon'],
                        'ZeitBis' => $stunde['ZeitBis'],
                    ];
                }
            }

            // Note: In Indiware format, each class has its Plan entries
            // but entries can reference multiple classes via PlKl
            // We need to collect ALL unique Plan entries across all classes
        }

        // Collect all unique Plan entries from all classes
        $allPlanEntries = [];
        foreach ($stundenplanKlassen['Klassen'] ?? [] as $klasse) {
            foreach ($klasse['Plan'] ?? [] as $planEntry) {
                // WICHTIG: Key muss PlTg + PlSt enthalten, NICHT nur PlUn!
                // Bei Doppelstunden ist PlUn gleich, aber PlSt unterschiedlich
                $key = implode('-', [
                    $planEntry['PlTg'],
                    $planEntry['PlSt'],
                    $planEntry['PlFa'] ?? '',
                    $planEntry['PlUn'] ?? 'no-un',
                ]);

                // Store with all its classes
                if (!isset($allPlanEntries[$key])) {
                    $allPlanEntries[$key] = [
                        'PlUn' => $planEntry['PlUn'] ?? null,
                        'PlTg' => (string) $planEntry['PlTg'],
                        'PlSt' => (string) $planEntry['PlSt'],
                        'PlFa' => $planEntry['PlFa'] ?? '',
                        'PlKl' => $planEntry['PlKl'] ?? [$klasse['Kurzform']],
                        'PlLe' => is_array($planEntry['PlLe'] ?? null)
                            ? $planEntry['PlLe']
                            : (isset($planEntry['PlLe']) ? [$planEntry['PlLe']] : []),
                        'PlRa' => is_array($planEntry['PlRa'] ?? null)
                            ? $planEntry['PlRa']
                            : (isset($planEntry['PlRa']) ? [$planEntry['PlRa']] : []),
                    ];
                }
            }
        }

        // Now create Klassen entries with their Plan references
        foreach ($stundenplanKlassen['Klassen'] ?? [] as $klasse) {
            $klasseData = [
                'Kurzform' => $klasse['Kurzform'],
                'Plan' => [],
            ];

            // Find all Plan entries that include this class
            foreach ($allPlanEntries as $planEntry) {
                if (in_array($klasse['Kurzform'], $planEntry['PlKl'])) {
                    $klasseData['Plan'][] = $planEntry;
                }
            }

            $result['Klassen'][] = $klasseData;
        }

        Log::info('Converted Indiware Export to internal format', [
            'klassen_count' => count($result['Klassen']),
            'zeitslots_count' => count($result['Zeitslots']),
            'plan_entries' => count($allPlanEntries),
        ]);

        return $result;
    }

    /**
     * Validate normalized data structure
     */
    public static function validate(array $data): bool
    {
        // Check required top-level keys
        if (!isset($data['Basisdaten']) || !isset($data['Zeitslots']) || !isset($data['Klassen'])) {
            return false;
        }

        // Check Basisdaten structure
        $requiredBasisdaten = ['DatumVon', 'DatumBis', 'SwVon', 'SwBis', 'TageProWoche'];
        foreach ($requiredBasisdaten as $key) {
            if (!isset($data['Basisdaten'][$key])) {
                return false;
            }
        }

        // Check Zeitslots structure
        if (!is_array($data['Zeitslots']) || empty($data['Zeitslots'])) {
            return false;
        }

        // Check Klassen structure
        if (!is_array($data['Klassen']) || empty($data['Klassen'])) {
            return false;
        }

        return true;
    }
}

