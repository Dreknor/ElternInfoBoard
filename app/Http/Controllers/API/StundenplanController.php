<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Stundenplan\Eintrag;
use App\Model\Stundenplan\Klasse;
use App\Model\Stundenplan\Lehrer;
use App\Model\Stundenplan\Raum;
use App\Model\Stundenplan\Schuljahr;
use App\Model\Stundenplan\Zeitslot;
use App\Model\Vertretung;
use App\Model\VertretungsplanAbsence;
use App\Model\VertretungsplanNews;
use App\Model\VertretungsplanWeek;
use App\Services\StundenplanDataProvider;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stundenplan API Controller
 *
 * Dieser Controller verwaltet alle API-Endpunkte für Stundenpläne.
 * Es werden Stundenpläne für Klassen, Lehrer und Räume bereitgestellt.
 * Zusätzlich werden Vertretungsinformationen einbezogen.
 *
 * @group Stundenplan
 */
class StundenplanController extends Controller
{
    /**
     * Alle verfügbaren Klassen abrufen
     *
     * Gibt eine Liste aller verfügbaren Klassen für das aktive Schuljahr zurück.
     * Eltern sehen nur die Klassen ihrer Kinder.
     *
     * @group Stundenplan
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "kurzform": "5a",
     *       "name": "Klasse 5a"
     *     },
     *     {
     *       "id": 2,
     *       "kurzform": "5b",
     *       "name": "Klasse 5b"
     *     }
     *   ]
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "message": "Keine Berechtigung zum Anzeigen von Stundenplänen"
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Kein aktives Schuljahr gefunden"
     * }
     *
     * @responseField success boolean Gibt an, ob die Anfrage erfolgreich war
     * @responseField data array Liste der verfügbaren Klassen
     * @responseField data[].id integer ID der Klasse
     * @responseField data[].kurzform string Kurzform der Klasse (z.B. "5a")
     * @responseField data[].name string Vollständiger Name der Klasse
     *
     * @return JsonResponse
     */
    public function getClasses(Request $request): JsonResponse
    {

        Log::debug('getClasses API called', ['user_id' => $request->user()->id]);
        // Check permissions
        $user = $request->user();
        if (!$user->can('view stundenplan')) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Anzeigen von Stundenplänen',
            ], 403);
        }

        try {
            $schuljahr = Schuljahr::where('is_active', true)->get();


            if (!$schuljahr) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kein aktives Schuljahr gefunden',
                ], 404);
            }

            $klassen = Klasse::whereIn('schuljahr_id', $schuljahr->pluck('id'))
                ->orderBy('kurzform')
                ->get(['id', 'kurzform', 'name']);


            $availableClasses = [];

            foreach ($klassen as $klasse) {
                $availableClasses[] = [
                    'id' => $klasse->id,
                    'kurzform' => $klasse->kurzform,
                    'name' => $klasse->name,
                ];

            }

            Log::debug('getClasses API returning', ['availableClasses' => $availableClasses]);

            /*
            // Filter for parents
            if ($user->hasRole('eltern')) {
                $allowedClasses = $this->getAllowedClassesForParent($user);
                $klassen = $klassen->filter(function ($klasse) use ($allowedClasses) {
                    return in_array($klasse->kurzform, $allowedClasses);
                })->values();
            }


*/
            return response()->json([
                'success' => true,
                'data' => $availableClasses,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Klassen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alle verfügbaren Lehrer abrufen
     *
     * Gibt eine Liste aller verfügbaren Lehrer zurück.
     * Diese Funktion erfordert die Berechtigung 'view stundenplan teacher'.
     *
     * @group Stundenplan
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "kuerzel": "MUE",
     *       "name": "Müller",
     *       "vorname": "Hans",
     *       "full_name": "Hans Müller"
     *     },
     *     {
     *       "id": 2,
     *       "kuerzel": "SCH",
     *       "name": "Schmidt",
     *       "vorname": "Anna",
     *       "full_name": "Anna Schmidt"
     *     }
     *   ]
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "message": "Keine Berechtigung zum Anzeigen von Lehrerstundenplänen"
     * }
     *
     * @responseField success boolean Gibt an, ob die Anfrage erfolgreich war
     * @responseField data array Liste der verfügbaren Lehrer
     * @responseField data[].id integer ID des Lehrers
     * @responseField data[].kuerzel string Kürzel des Lehrers (z.B. "MUE")
     * @responseField data[].name string Nachname des Lehrers
     * @responseField data[].vorname string Vorname des Lehrers
     * @responseField data[].full_name string Vollständiger Name des Lehrers
     *
     * @return JsonResponse
     */
    public function getTeachers(Request $request): JsonResponse
    {
        // Check permissions
        if (!$request->user()->can('view stundenplan teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Anzeigen von Lehrerstundenplänen',
            ], 403);
        }

        try {
            $lehrer = Lehrer::orderBy('kuerzel')
                ->get(['id', 'kuerzel', 'name', 'vorname']);

            return response()->json([
                'success' => true,
                'data' => $lehrer->map(function ($lehrer) {
                    return [
                        'id' => $lehrer->id,
                        'kuerzel' => $lehrer->kuerzel,
                        'name' => $lehrer->name,
                        'vorname' => $lehrer->vorname,
                        'full_name' => $lehrer->full_name ?? $lehrer->kuerzel,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Lehrer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alle verfügbaren Räume abrufen
     *
     * Gibt eine Liste aller verfügbaren Räume zurück.
     * Diese Funktion erfordert die Berechtigung 'view stundenplan room'.
     *
     * @group Stundenplan
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "kuerzel": "A101",
     *       "name": "Raum A101"
     *     },
     *     {
     *       "id": 2,
     *       "kuerzel": "B205",
     *       "name": "Computerraum B205"
     *     }
     *   ]
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "message": "Keine Berechtigung zum Anzeigen von Raumstundenplänen"
     * }
     *
     * @responseField success boolean Gibt an, ob die Anfrage erfolgreich war
     * @responseField data array Liste der verfügbaren Räume
     * @responseField data[].id integer ID des Raums
     * @responseField data[].kuerzel string Kürzel des Raums (z.B. "A101")
     * @responseField data[].name string Name des Raums
     *
     * @return JsonResponse
     */
    public function getRooms(Request $request): JsonResponse
    {
        // Check permissions
        if (!$request->user()->can('view stundenplan room')) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Anzeigen von Raumstundenplänen',
            ], 403);
        }

        try {
            $raeume = Raum::orderBy('kuerzel')
                ->get(['id', 'kuerzel', 'name']);

            return response()->json([
                'success' => true,
                'data' => $raeume,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Räume: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stundenplan für eine spezifische Klasse abrufen
     *
     * Gibt den Stundenplan einer bestimmten Klasse zurück, inklusive aller Einträge und Vertretungen.
     * Die Klasse kann über ihre ID oder Kurzform identifiziert werden.
     * Eltern haben nur Zugriff auf die Stundenpläne ihrer Kinder.
     *
     * @group Stundenplan
     *
     * @authenticated
     *
     * @urlParam classId string required Die ID oder Kurzform der Klasse (z.B. "5a" oder "1"). Example: 5a
     *
     * @response 200 scenario="Erfolgreiche Anfrage" {
     *   "success": true,
     *   "data": {
     *     "class": {
     *       "id": 1,
     *       "kurzform": "5a",
     *       "name": "Klasse 5a"
     *     },
     *     "schuljahr": {
     *       "name": "2025/2026",
     *       "datum_von": "2025-08-01",
     *       "datum_bis": "2026-07-31"
     *     },
     *     "timetable": {
     *       "1": {
     *         "1": [
     *           {
     *             "id": 1,
     *             "unterrichts_id": "MAT-5A-01",
     *             "fach": {
     *               "kuerzel": "MAT",
     *               "name": "Mathematik",
     *               "farbe": "#FF5733"
     *             },
     *             "lehrer": [
     *               {
     *                 "kuerzel": "MUE",
     *                 "name": "Müller"
     *               }
     *             ],
     *             "raeume": [
     *               {
     *                 "kuerzel": "A101",
     *                 "name": "Raum A101"
     *               }
     *             ],
     *             "klassen": ["5a"],
     *             "zeit": {
     *               "stunde": 1,
     *               "von": "08:00:00",
     *               "bis": "08:45:00"
     *             }
     *           }
     *         ]
     *       }
     *     },
     *     "vertretungen": {}
     *   }
     * }
     *
     * @response 403 scenario="Keine Berechtigung" {
     *   "success": false,
     *   "message": "Keine Berechtigung zum Anzeigen von Stundenplänen"
     * }
     *
     * @response 404 scenario="Klasse nicht gefunden" {
     *   "success": false,
     *   "message": "Klasse nicht gefunden"
     * }
     *
     * @responseField success boolean Gibt an, ob die Anfrage erfolgreich war
     * @responseField data object Stundenplan-Daten
     * @responseField data.class object Informationen zur Klasse
     * @responseField data.class.id integer ID der Klasse
     * @responseField data.class.kurzform string Kurzform der Klasse
     * @responseField data.class.name string Name der Klasse
     * @responseField data.schuljahr object Informationen zum Schuljahr
     * @responseField data.schuljahr.name string Name des Schuljahres
     * @responseField data.schuljahr.datum_von string Startdatum des Schuljahres
     * @responseField data.schuljahr.datum_bis string Enddatum des Schuljahres
     * @responseField data.timetable object Stundenplan-Einträge nach Wochentag und Stunde
     * @responseField data.vertretungen object Vertretungen gruppiert nach Wochentag
     *
     * @param Request $request
     * @param string $classId Class ID or Kurzform
     * @return JsonResponse
     */
    public function getTimetableByClass(Request $request, string $classId): JsonResponse
    {
        $user = $request->user();


        if (!$user->can('view stundenplan')) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Anzeigen von Stundenplänen',
            ], 403);
        }

        try {
            $schuljahr = Schuljahr::where('is_active', true)->first();

            if (!$schuljahr) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kein aktives Schuljahr gefunden',
                ], 404);
            }

            // Find class by ID or Kurzform
            $klasse = Klasse::where('schuljahr_id', $schuljahr->id)
                ->where('kurzform', 'like', $classId . '%')
                ->first();


            if (!$klasse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Klasse nicht gefunden',
                ], 404);
            }
/*
            // Check parent permissions
            if ($user->hasRole('eltern')) {
                $allowedClasses = $this->getAllowedClassesForParent($user);
                if (!in_array($klasse->kurzform, $allowedClasses)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Keine Berechtigung für diese Klasse',
                    ], 403);
                }
            }
*/
            $timetable = $this->buildTimetableForClassFromDB($schuljahr, $klasse);



            $vertretungen = $this->getVertretungenForClass($klasse->kurzform);


            return response()->json([
                'success' => true,
                'data' => [
                    'class' => [
                        'id' => $klasse->id,
                        'kurzform' => $klasse->kurzform,
                        'name' => $klasse->name,
                    ],
                    'schuljahr' => [
                        'name' => $schuljahr->name,
                        'datum_von' => $schuljahr->datum_von,
                        'datum_bis' => $schuljahr->datum_bis,
                    ],
                    'timetable' => $timetable,
                    'vertretungen' => $vertretungen,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen des Stundenplans: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stundenplan für einen spezifischen Lehrer abrufen
     *
     * Gibt den Stundenplan eines bestimmten Lehrers zurück, inklusive aller Einträge und Vertretungen.
     * Der Lehrer kann über seine ID oder sein Kürzel identifiziert werden.
     * Diese Funktion erfordert die Berechtigung 'view stundenplan teacher'.
     *
     * @group Stundenplan
     *
     * @authenticated
     *
     * @urlParam teacherId string required Die ID oder das Kürzel des Lehrers (z.B. "MUE" oder "1"). Example: MUE
     *
     * @response 200 scenario="Erfolgreiche Anfrage" {
     *   "success": true,
     *   "data": {
     *     "teacher": {
     *       "id": 1,
     *       "kuerzel": "MUE",
     *       "name": "Müller",
     *       "vorname": "Hans",
     *       "full_name": "Hans Müller"
     *     },
     *     "schuljahr": {
     *       "name": "2025/2026",
     *       "datum_von": "2025-08-01",
     *       "datum_bis": "2026-07-31"
     *     },
     *     "timetable": {
     *       "1": {
     *         "1": [
     *           {
     *             "id": 1,
     *             "unterrichts_id": "MAT-5A-01",
     *             "fach": {
     *               "kuerzel": "MAT",
     *               "name": "Mathematik",
     *               "farbe": "#FF5733"
     *             },
     *             "lehrer": [
     *               {
     *                 "kuerzel": "MUE",
     *                 "name": "Müller"
     *               }
     *             ],
     *             "raeume": [
     *               {
     *                 "kuerzel": "A101",
     *                 "name": "Raum A101"
     *               }
     *             ],
     *             "klassen": ["5a", "5b"],
     *             "zeit": {
     *               "stunde": 1,
     *               "von": "08:00:00",
     *               "bis": "08:45:00"
     *             }
     *           }
     *         ]
     *       }
     *     },
     *     "vertretungen": {}
     *   }
     * }
     *
     * @response 403 scenario="Keine Berechtigung" {
     *   "success": false,
     *   "message": "Keine Berechtigung zum Anzeigen von Lehrerstundenplänen"
     * }
     *
     * @response 404 scenario="Lehrer nicht gefunden" {
     *   "success": false,
     *   "message": "Lehrer nicht gefunden"
     * }
     *
     * @responseField success boolean Gibt an, ob die Anfrage erfolgreich war
     * @responseField data object Stundenplan-Daten
     * @responseField data.teacher object Informationen zum Lehrer
     * @responseField data.teacher.id integer ID des Lehrers
     * @responseField data.teacher.kuerzel string Kürzel des Lehrers
     * @responseField data.teacher.name string Nachname des Lehrers
     * @responseField data.teacher.vorname string Vorname des Lehrers
     * @responseField data.teacher.full_name string Vollständiger Name des Lehrers
     * @responseField data.schuljahr object Informationen zum Schuljahr
     * @responseField data.schuljahr.name string Name des Schuljahres
     * @responseField data.schuljahr.datum_von string Startdatum des Schuljahres
     * @responseField data.schuljahr.datum_bis string Enddatum des Schuljahres
     * @responseField data.timetable object Stundenplan-Einträge nach Wochentag und Stunde
     * @responseField data.vertretungen object Vertretungen gruppiert nach Wochentag
     *
     * @param Request $request
     * @param string $teacherId Teacher ID or Kuerzel
     * @return JsonResponse
     */
    public function getTimetableByTeacher(Request $request, string $teacherId): JsonResponse
    {
        if (!$request->user()->can('view stundenplan teacher')) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Anzeigen von Lehrerstundenplänen',
            ], 403);
        }

        try {
            $schuljahr = Schuljahr::where('is_active', true)->first();

            if (!$schuljahr) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kein aktives Schuljahr gefunden',
                ], 404);
            }

            // Find teacher by ID or Kuerzel
            $lehrer = Lehrer::where(function ($query) use ($teacherId) {
                $query->where('id', $teacherId)
                    ->orWhere('kuerzel', $teacherId);
            })->first();

            if (!$lehrer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lehrer nicht gefunden',
                ], 404);
            }

            $timetable = $this->buildTimetableForTeacherFromDB($schuljahr, $lehrer);
            $vertretungen = $this->getVertretungenForTeacher($lehrer->kuerzel);

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher' => [
                        'id' => $lehrer->id,
                        'kuerzel' => $lehrer->kuerzel,
                        'name' => $lehrer->name,
                        'vorname' => $lehrer->vorname,
                        'full_name' => $lehrer->full_name ?? $lehrer->kuerzel,
                    ],
                    'schuljahr' => [
                        'name' => $schuljahr->name,
                        'datum_von' => $schuljahr->datum_von,
                        'datum_bis' => $schuljahr->datum_bis,
                    ],
                    'timetable' => $timetable,
                    'vertretungen' => $vertretungen,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen des Stundenplans: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stundenplan für einen spezifischen Raum abrufen
     *
     * Gibt den Stundenplan eines bestimmten Raums zurück, inklusive aller Einträge.
     * Der Raum kann über seine ID oder sein Kürzel identifiziert werden.
     * Diese Funktion erfordert die Berechtigung 'view stundenplan room'.
     *
     * @group Stundenplan
     *
     * @authenticated
     *
     * @urlParam roomId string required Die ID oder das Kürzel des Raums (z.B. "A101" oder "1"). Example: A101
     *
     * @response 200 scenario="Erfolgreiche Anfrage" {
     *   "success": true,
     *   "data": {
     *     "room": {
     *       "id": 1,
     *       "kuerzel": "A101",
     *       "name": "Raum A101"
     *     },
     *     "schuljahr": {
     *       "name": "2025/2026",
     *       "datum_von": "2025-08-01",
     *       "datum_bis": "2026-07-31"
     *     },
     *     "timetable": {
     *       "1": {
     *         "1": [
     *           {
     *             "id": 1,
     *             "unterrichts_id": "MAT-5A-01",
     *             "fach": {
     *               "kuerzel": "MAT",
     *               "name": "Mathematik",
     *               "farbe": "#FF5733"
     *             },
     *             "lehrer": [
     *               {
     *                 "kuerzel": "MUE",
     *                 "name": "Müller"
     *               }
     *             ],
     *             "raeume": [
     *               {
     *                 "kuerzel": "A101",
     *                 "name": "Raum A101"
     *               }
     *             ],
     *             "klassen": ["5a"],
     *             "zeit": {
     *               "stunde": 1,
     *               "von": "08:00:00",
     *               "bis": "08:45:00"
     *             }
     *           }
     *         ]
     *       }
     *     }
     *   }
     * }
     *
     * @response 403 scenario="Keine Berechtigung" {
     *   "success": false,
     *   "message": "Keine Berechtigung zum Anzeigen von Raumstundenplänen"
     * }
     *
     * @response 404 scenario="Raum nicht gefunden" {
     *   "success": false,
     *   "message": "Raum nicht gefunden"
     * }
     *
     * @responseField success boolean Gibt an, ob die Anfrage erfolgreich war
     * @responseField data object Stundenplan-Daten
     * @responseField data.room object Informationen zum Raum
     * @responseField data.room.id integer ID des Raums
     * @responseField data.room.kuerzel string Kürzel des Raums
     * @responseField data.room.name string Name des Raums
     * @responseField data.schuljahr object Informationen zum Schuljahr
     * @responseField data.schuljahr.name string Name des Schuljahres
     * @responseField data.schuljahr.datum_von string Startdatum des Schuljahres
     * @responseField data.schuljahr.datum_bis string Enddatum des Schuljahres
     * @responseField data.timetable object Stundenplan-Einträge nach Wochentag und Stunde
     *
     * @param Request $request
     * @param string $roomId Room ID or Kuerzel
     * @return JsonResponse
     */
    public function getTimetableByRoom(Request $request, string $roomId): JsonResponse
    {
        if (!$request->user()->can('view stundenplan room')) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Anzeigen von Raumstundenplänen',
            ], 403);
        }

        try {
            $schuljahr = Schuljahr::where('is_active', true)->first();

            if (!$schuljahr) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kein aktives Schuljahr gefunden',
                ], 404);
            }

            // Find room by ID or Kuerzel
            $raum = Raum::where(function ($query) use ($roomId) {
                $query->where('id', $roomId)
                    ->orWhere('kuerzel', $roomId);
            })->first();

            if (!$raum) {
                return response()->json([
                    'success' => false,
                    'message' => 'Raum nicht gefunden',
                ], 404);
            }

            $timetable = $this->buildTimetableForRoomFromDB($schuljahr, $raum);

            return response()->json([
                'success' => true,
                'data' => [
                    'room' => [
                        'id' => $raum->id,
                        'kuerzel' => $raum->kuerzel,
                        'name' => $raum->name,
                    ],
                    'schuljahr' => [
                        'name' => $schuljahr->name,
                        'datum_von' => $schuljahr->datum_von,
                        'datum_bis' => $schuljahr->datum_bis,
                    ],
                    'timetable' => $timetable,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen des Stundenplans: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build timetable array for a class from database
     */
    private function buildTimetableForClassFromDB($schuljahr, $klasse)
    {
        $timetable = [];
Log::debug('Erstelle Plan für Klasse', ['schuljahr' => $schuljahr->name, 'klasse' => $klasse->kurzform]);
        // Initialize empty timetable
        for ($tag = 1; $tag <= 5; $tag++) {
            $timetable[$tag] = [];
        }

        // Get all entries for this class
        $eintraege = Eintrag::where('schuljahr_id', $schuljahr->id)
            ->whereHas('klassen', function ($query) use ($klasse) {
                $query->where('stundenplan_klassen.id', $klasse->id);
            })
            ->with(['zeitslot', 'fach', 'lehrer', 'raeume', 'klassen'])
            ->orderBy('wochentag')
            ->orderBy('zeitslot_id')
            ->get();


        foreach ($eintraege as $eintrag) {
            $tag = $eintrag->wochentag;

            if (!isset($timetable[$tag][$eintrag->zeitslot->stunde])) {
                $timetable[$tag][$eintrag->zeitslot->stunde] = [];
            }


            $timetable[$tag][$eintrag->zeitslot->stunde][] = [
                'id' => $eintrag->id,
                'unterrichts_id' => $eintrag->unterrichts_id,
                'fach' => [
                    'kuerzel' => $eintrag->fach->kuerzel,
                    'name' => $eintrag->fach->name,
                    'farbe' => $eintrag->fach->farbe,
                ],
                'lehrer' => $eintrag->lehrer->map(function ($l) {
                    return [
                        'kuerzel' => $l->kuerzel,
                        'name' => $l->name,
                    ];
                }),
                'raeume' => $eintrag->raeume->map(function ($r) {
                    return [
                        'kuerzel' => $r->kuerzel,
                        'name' => $r->name,
                    ];
                }),
                'klassen' => $eintrag->klassen->map(function ($k) {
                    return $k->kurzform;
                }),
                'zeit' => [
                    'stunde' => $eintrag->zeitslot->stunde,
                    'von' => $eintrag->zeitslot->zeit_von,
                    'bis' => $eintrag->zeitslot->zeit_bis,
                ],
            ];

            Log::debug('Eintrag hinzugefügt', [
                'tag' => $tag,
                'stunde' => $eintrag->zeitslot->stunde,
                'fach' => $eintrag->fach->kuerzel,
                'lehrer' => $eintrag->lehrer->pluck('kuerzel')->toArray(),
                'raeume' => $eintrag->raeume->pluck('kuerzel')->toArray(),
                'klassen' => $eintrag->klassen->pluck('kurzform')->toArray(),
            ]);
        }


        return $timetable;
    }

    /**
     * Build timetable array for a teacher from database
     */
    private function buildTimetableForTeacherFromDB($schuljahr, $lehrer)
    {
        $timetable = [];

        // Initialize empty timetable
        for ($tag = 1; $tag <= 5; $tag++) {
            $timetable[$tag] = [];
        }

        // Get all entries for this teacher
        $eintraege = Eintrag::where('schuljahr_id', $schuljahr->id)
            ->whereHas('lehrer', function ($query) use ($lehrer) {
                $query->where('stundenplan_lehrer.id', $lehrer->id);
            })
            ->with(['zeitslot', 'fach', 'lehrer', 'raeume', 'klassen'])
            ->orderBy('wochentag')
            ->orderBy('zeitslot_id')
            ->get();

        // Collect entries temporarily for grouping
        $tempEntries = [];

        foreach ($eintraege as $eintrag) {
            $tag = $eintrag->wochentag;
            $stunde = $eintrag->zeitslot->stunde;

            if (!isset($tempEntries[$tag][$stunde])) {
                $tempEntries[$tag][$stunde] = [];
            }

            $tempEntries[$tag][$stunde][] = [
                'id' => $eintrag->id,
                'unterrichts_id' => $eintrag->unterrichts_id,
                'fach' => [
                    'kuerzel' => $eintrag->fach->kuerzel,
                    'name' => $eintrag->fach->name,
                    'farbe' => $eintrag->fach->farbe,
                ],
                'lehrer' => $eintrag->lehrer->map(function ($l) {
                    return [
                        'kuerzel' => $l->kuerzel,
                        'name' => $l->name,
                    ];
                })->toArray(),
                'raeume' => $eintrag->raeume->map(function ($r) {
                    return [
                        'kuerzel' => $r->kuerzel,
                        'name' => $r->name,
                    ];
                })->toArray(),
                'klassen' => $eintrag->klassen->map(function ($k) {
                    return $k->kurzform;
                })->toArray(),
                'zeit' => [
                    'stunde' => $eintrag->zeitslot->stunde,
                    'von' => $eintrag->zeitslot->zeit_von,
                    'bis' => $eintrag->zeitslot->zeit_bis,
                ],
            ];
        }

        // Group entries by room and subject
        foreach ($tempEntries as $tag => $stunden) {
            foreach ($stunden as $stunde => $entries) {
                $grouped = [];

                foreach ($entries as $entry) {
                    // Create a key based on room and subject
                    $raumKey = json_encode($entry['raeume']);
                    $fachKey = $entry['fach']['kuerzel'];
                    $key = $raumKey . '|' . $fachKey;

                    if (!isset($grouped[$key])) {
                        $grouped[$key] = $entry;
                    } else {
                        // Merge classes
                        $grouped[$key]['klassen'] = array_unique(
                            array_merge($grouped[$key]['klassen'], $entry['klassen'])
                        );
                    }
                }

                // Add grouped entries to timetable
                if (!isset($timetable[$tag][$stunde])) {
                    $timetable[$tag][$stunde] = [];
                }

                foreach ($grouped as $groupedEntry) {
                    $timetable[$tag][$stunde][] = $groupedEntry;
                }
            }
        }

        return $timetable;
    }

    /**
     * Build timetable array for a room from database
     */
    private function buildTimetableForRoomFromDB($schuljahr, $raum)
    {
        $timetable = [];

        // Initialize empty timetable
        for ($tag = 1; $tag <= 5; $tag++) {
            $timetable[$tag] = [];
        }

        // Get all entries for this room
        $eintraege = Eintrag::where('schuljahr_id', $schuljahr->id)
            ->whereHas('raeume', function ($query) use ($raum) {
                $query->where('stundenplan_raeume.id', $raum->id);
            })
            ->with(['zeitslot', 'fach', 'lehrer', 'raeume', 'klassen'])
            ->orderBy('wochentag')
            ->orderBy('zeitslot_id')
            ->get();

        foreach ($eintraege as $eintrag) {
            $tag = $eintrag->wochentag;

            if (!isset($timetable[$tag][$eintrag->zeitslot->stunde])) {
                $timetable[$tag][$eintrag->zeitslot->stunde] = [];
            }

            $timetable[$tag][$eintrag->zeitslot->stunde][] = [
                'id' => $eintrag->id,
                'unterrichts_id' => $eintrag->unterrichts_id,
                'fach' => [
                    'kuerzel' => $eintrag->fach->kuerzel,
                    'name' => $eintrag->fach->name,
                    'farbe' => $eintrag->fach->farbe,
                ],
                'lehrer' => $eintrag->lehrer->map(function ($l) {
                    return [
                        'kuerzel' => $l->kuerzel,
                        'name' => $l->name,
                    ];
                }),
                'raeume' => $eintrag->raeume->map(function ($r) {
                    return [
                        'kuerzel' => $r->kuerzel,
                        'name' => $r->name,
                    ];
                }),
                'klassen' => $eintrag->klassen->map(function ($k) {
                    return $k->kurzform;
                }),
                'zeit' => [
                    'stunde' => $eintrag->zeitslot->stunde,
                    'von' => $eintrag->zeitslot->zeit_von,
                    'bis' => $eintrag->zeitslot->zeit_bis,
                ],
            ];
        }

        return $timetable;
    }

    /**
     * Get Vertretungen for class
     */
    private function getVertretungenForClass($classKurzform)
    {
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        try {
            return Vertretung::where('klasse', $classKurzform)
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy(function ($item) {
                    return Carbon::parse($item->date)->dayOfWeekIso;
                });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get Vertretungen for teacher
     */
    private function getVertretungenForTeacher($teacherKuerzel)
    {
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        try {
            return Vertretung::where('lehrer', 'LIKE', "%{$teacherKuerzel}%")
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy(function ($item) {
                    return Carbon::parse($item->date)->dayOfWeekIso;
                });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get allowed classes for a parent user
     */
    private function getAllowedClassesForParent($user)
    {
        $allowedClasses = [];

        // Get all children of the parent
        $children = $user->childRelations()->with('child.groups')->get();

        foreach ($children as $childRelation) {
            if ($childRelation->child && $childRelation->child->groups) {
                foreach ($childRelation->child->groups as $group) {
                    // Use group shortname as class identifier
                    if ($group->shortname) {
                        $allowedClasses[] = $group->shortname;
                    }
                }
            }
        }

        return array_unique($allowedClasses);
    }
}






