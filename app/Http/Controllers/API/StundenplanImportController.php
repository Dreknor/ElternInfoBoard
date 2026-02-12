<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiImportStundenplanRequest;
use App\Services\StundenplanDataAdapter;
use App\Services\StundenplanDatabaseImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StundenplanImportController extends Controller
{
    /**
     * Import stundenplan data via API
     *
     * @param ApiImportStundenplanRequest $request
     * @return JsonResponse
     */
    public function import(ApiImportStundenplanRequest $request): JsonResponse
    {
        try {
            // Get JSON data from request body
            $content = $request->getContent();
            $data = json_decode($content, true);

            if (!$data) {
                throw new \Exception('Ungültige JSON-Daten');
            }

            // Extract schulform and beschreibung if provided
            $schulform = $data['schulform'] ?? null;
            $beschreibung = $data['beschreibung'] ?? null;

            // Remove metadata from the data before normalizing
            unset($data['key']);
            unset($data['schulform']);
            unset($data['beschreibung']);

            // Normalize data format (supports both direct and Indiware export format)
            $normalizedData = StundenplanDataAdapter::normalize($data);

            // Validate normalized data
            if (!StundenplanDataAdapter::validate($normalizedData)) {
                throw new \Exception('Datenvalidierung fehlgeschlagen');
            }

            // Import to database with schulform
            $importer = new StundenplanDatabaseImporter();
            $importStats = $importer->import($normalizedData, $schulform, $beschreibung);

            // Store temporarily for backup/debugging (optional)
            $filename = 'stundenplan/import_' . date('Y-m-d_H-i-s') . '.json';
            Storage::disk('local')->put($filename, json_encode($normalizedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Clear cache
            Cache::forget('stundenplan_data');
            Cache::forget('stundenplan_data_from_db');

            Log::info('Stundenplan imported via API', array_merge([
                'filename' => $filename,
            ], $importStats));

            return response()->json([
                'success' => true,
                'message' => 'Stundenplan erfolgreich in Datenbank importiert',
                'data' => array_merge([
                    'filename' => $filename,
                ], $importStats),
            ]);

        } catch (\Exception $e) {
            Log::error('Stundenplan import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import fehlgeschlagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get import status and info
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        try {
            if (!Storage::disk('local')->exists('stundenplan/current.json')) {
                return response()->json([
                    'success' => true,
                    'imported' => false,
                    'message' => 'Noch kein Stundenplan importiert',
                ]);
            }

            $content = Storage::disk('local')->get('stundenplan/current.json');
            $data = json_decode($content, true);

            return response()->json([
                'success' => true,
                'imported' => true,
                'data' => [
                    'basisdaten' => $data['Basisdaten'] ?? null,
                    'klassen_count' => count($data['Klassen'] ?? []),
                    'zeitslots_count' => count($data['Zeitslots'] ?? []),
                    'last_modified' => date('d.m.Y H:i:s', Storage::disk('local')->lastModified('stundenplan/current.json')),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen des Status: ' . $e->getMessage(),
            ], 500);
        }
    }
}




