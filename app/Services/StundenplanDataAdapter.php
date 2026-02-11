<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class StundenplanDataAdapter
{
    /**
     * Normalize imported data to internal format
     * Supports both:
     * - Direct format (Basisdaten, Zeitslots, Klassen)
     * - Indiware Export format (Gesamtexport.Stundenplan.StundenplanKlassen)
     */
    public static function normalize(array $data): array
    {
        // Check if it's already in the correct format
        if (isset($data['Basisdaten']) && isset($data['Zeitslots']) && isset($data['Klassen'])) {
            return $data;
        }

        // Check if it's Indiware Gesamtexport format
        if (isset($data['Gesamtexport']['Stundenplan']['StundenplanKlassen'])) {
            return self::convertFromIndiwareExport($data['Gesamtexport']['Stundenplan']['StundenplanKlassen']);
        }

        throw new \Exception('Unbekanntes Datenformat. Erwartete Struktur: Basisdaten, Zeitslots, Klassen oder Indiware Gesamtexport');
    }

    /**
     * Convert from Indiware Gesamtexport to internal format
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

