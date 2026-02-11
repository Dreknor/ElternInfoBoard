<?php

namespace App\Http\Controllers;

use App\Model\Vertretung;
use App\Model\VertretungsplanAbsence;
use App\Model\VertretungsplanNews;
use App\Model\VertretungsplanWeek;
use App\Services\StundenplanDataAdapter;
use App\Services\StundenplanDatabaseImporter;
use App\Services\StundenplanDataProvider;
use App\Settings\StundenplanSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StundenplanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the stundenplan overview with tabs
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('view stundenplan')) {
            return redirect(url('home'))->with([
                'type' => 'error',
                'Meldung' => 'Sie haben keine Berechtigung, den Stundenplan anzusehen.',
            ]);
        }


        $data = $this->getStundenplanData();
        $view = $request->get('view', 'class');

        // If no data available, show info message
        if (!$data) {
            return view('stundenplan.no-data', [
                'message' => 'Noch kein Stundenplan importiert. Bitte importieren Sie zuerst einen Stundenplan.',
            ]);
        }

        $user = $request->user();
        $allClasses = $this->extractClasses($data);
        $classes = $allClasses;

        // Filter classes for parents
        if ($user->hasRole('eltern')) {
            $allowedClasses = $this->getAllowedClassesForParent($user);
            $classes = array_intersect($allClasses, $allowedClasses);
        }

        return view('stundenplan.index', [
            'data' => $data,
            'currentView' => $view,
            'classes' => $classes,
            'teachers' => $this->extractTeachers($data),
            'rooms' => $this->extractRooms($data),
            'isParent' => $user->hasRole('eltern'),
            'canViewTeacher' => $user->can('view stundenplan teacher'),
            'canViewRoom' => $user->can('view stundenplan room'),
        ]);
    }

    /**
     * Display the teacher view
     */
    public function lehrerAnsicht(Request $request, $teacher)
    {
        if (!$request->user()->can('view stundenplan')) {
            return redirect(url('home'))->with([
                'type' => 'error',
                'Meldung' => 'Sie haben keine Berechtigung, den Stundenplan anzusehen.',
            ]);
        }

        // Check permission for teacher view
        if (!$request->user()->can('view stundenplan teacher')) {
            return redirect()->route('stundenplan.index')->with([
                'type' => 'error',
                'Meldung' => 'Sie haben keine Berechtigung, die Lehreransicht anzusehen.',
            ]);
        }

        $data = $this->getStundenplanData();

        // If no data available, redirect to index with message
        if (!$data) {
            return redirect()->route('stundenplan.index')->with([
                'type' => 'warning',
                'Meldung' => 'Noch kein Stundenplan importiert.',
            ]);
        }

        $timetable = $this->buildTimetableForTeacher($data, $teacher);

        // Get substitutions for the teacher for the current week
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        try {
            $vertretungen = Vertretung::whereJsonContains('lehrer', $teacher)
                ->orWhere('lehrer', 'LIKE', "%{$teacher}%")
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy(function($item) {
                    return Carbon::parse($item->date)->dayOfWeekIso;
                });
        } catch (\Exception $e) {
            $vertretungen = collect();
        }

        try {
            // Get news for the current week
            $news = VertretungsplanNews::where('start', '<=', $endDate)
                ->where('end', '>=', $startDate)
                ->get();
        } catch (\Exception $e) {
            $news = collect();
        }

        try {
            // Get absences for this week
            $absences = VertretungsplanAbsence::where('start_date', '<=', $endDate)
                ->where(function($query) use ($startDate) {
                    $query->where('end_date', '>=', $startDate)
                        ->orWhereNull('end_date');
                })
                ->get();
        } catch (\Exception $e) {
            $absences = collect();
        }

        try {
            // Get week type (A/B week)
            $currentWeek = VertretungsplanWeek::whereDate('week', '<=', Carbon::now())
                ->orderBy('week', 'desc')
                ->first();
        } catch (\Exception $e) {
            $currentWeek = null;
        }

        return view('stundenplan.lehrer', [
            'teacher' => $teacher,
            'timetable' => $timetable,
            'basisdaten' => $data['Basisdaten'],
            'zeitslots' => $data['Zeitslots'],
            'vertretungen' => $vertretungen,
            'news' => $news,
            'absences' => $absences,
            'currentWeek' => $currentWeek,
            'startDate' => $startDate,
        ]);
    }

    /**
     * Display the class view
     */
    public function klassenAnsicht(Request $request, $class)
    {
        // Check if user is parent and restrict to their child's classes
        $user = $request->user();

        if (!$user->can('view stundenplan')) {
            return redirect(url('home'))->with([
                'type' => 'error',
                'Meldung' => 'Sie haben keine Berechtigung, den Stundenplan anzusehen.',
            ]);
        }

        // Check parent permissions
        if ($user->hasRole('eltern')) {
            $allowedClasses = $this->getAllowedClassesForParent($user);
            if (!in_array($class, $allowedClasses)) {
                return redirect()->route('stundenplan.index')->with([
                    'type' => 'error',
                    'Meldung' => 'Sie haben keine Berechtigung, diesen Stundenplan anzusehen.',
                ]);
            }
        }

        $data = $this->getStundenplanData();

        // If no data available, redirect to index with message
        if (!$data) {
            return redirect()->route('stundenplan.index')->with([
                'type' => 'warning',
                'Meldung' => 'Noch kein Stundenplan importiert.',
            ]);
        }

        $timetable = $this->buildTimetableForClass($data, $class);

        // Get substitutions for the next 5 days (current week)
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        try {
            $vertretungen = Vertretung::where('klasse', $class)
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy(function($item) {
                    return Carbon::parse($item->date)->dayOfWeekIso;
                });
        } catch (\Exception $e) {

        }

        try {
            // Get news for the current week
            $news = VertretungsplanNews::where('start', '<=', $endDate)
                ->where('end', '>=', $startDate)
                ->get();
        } catch (\Exception $e) {

        }

        try {
            // Get absences for this week
            $absences = VertretungsplanAbsence::where('start_date', '<=', $endDate)
                ->where(function($query) use ($startDate) {
                    $query->where('end_date', '>=', $startDate)
                        ->orWhereNull('end_date');
                })
                ->get();
        } catch (\Exception $e) {

        }

        try {
            // Get week type (A/B week)
            $currentWeek = VertretungsplanWeek::whereDate('week', '<=', Carbon::now())
                ->orderBy('week', 'desc')
                ->first();
        } catch (\Exception $e) {

        }

        return view('stundenplan.klassen', [
            'class' => $class,
            'timetable' => $timetable,
            'basisdaten' => $data['Basisdaten'],
            'zeitslots' => $data['Zeitslots'],
            'vertretungen' => $vertretungen,
            'news' => $news,
            'absences' => $absences,
            'currentWeek' => $currentWeek,
            'startDate' => $startDate,
        ]);
    }


    /**
     * Get stundenplan data from database ONLY
     * Returns null if no data available
     */
    private function getStundenplanData()
    {
        $dataProvider = new StundenplanDataProvider();
        return $dataProvider->getStundenplanData();
    }

    /**
     * Extract all unique classes
     */
    private function extractClasses($data)
    {
        $classes = [];

        // Defensive check: ensure Klassen exists and is an array
        if (!isset($data['Klassen']) || !is_array($data['Klassen'])) {
            return $classes;
        }

        foreach ($data['Klassen'] as $klasse) {
            if (isset($klasse['Kurzform'])) {
                $classes[] = $klasse['Kurzform'];
            }
        }

        return array_unique($classes);
    }

    /**
     * Extract all unique teachers
     */
    private function extractTeachers($data)
    {
        $teachers = [];

        // Defensive check: ensure Klassen exists and is an array
        if (!isset($data['Klassen']) || !is_array($data['Klassen'])) {
            return $teachers;
        }

        foreach ($data['Klassen'] as $klasse) {
            if (!isset($klasse['Plan']) || !is_array($klasse['Plan'])) {
                continue;
            }

            foreach ($klasse['Plan'] as $entry) {
                if (isset($entry['PlLe']) && is_array($entry['PlLe'])) {
                    $teachers = array_merge($teachers, $entry['PlLe']);
                }
            }
        }

        return array_unique($teachers);
    }

    /**
     * Extract all unique rooms
     */
    private function extractRooms($data)
    {
        $rooms = [];

        // Defensive check: ensure Klassen exists and is an array
        if (!isset($data['Klassen']) || !is_array($data['Klassen'])) {
            return $rooms;
        }

        foreach ($data['Klassen'] as $klasse) {
            if (!isset($klasse['Plan']) || !is_array($klasse['Plan'])) {
                continue;
            }

            foreach ($klasse['Plan'] as $entry) {
                if (isset($entry['PlRa']) && is_array($entry['PlRa'])) {
                    $rooms = array_merge($rooms, $entry['PlRa']);
                }
            }
        }

        return array_unique($rooms);
    }

    /**
     * Build timetable array for a specific class
     */
    private function buildTimetableForClass($data, $className)
    {
        $timetable = [];

        // Initialize empty timetable
        for ($tag = 1; $tag <= 5; $tag++) {
            for ($stunde = 1; $stunde <= 6; $stunde++) {
                $timetable[$tag][$stunde] = null;
            }
        }

        // Find the class data
        foreach ($data['Klassen'] as $klasse) {
            if ($klasse['Kurzform'] === $className) {
                // Fill timetable with plan entries
                foreach ($klasse['Plan'] as $entry) {
                    // Skip entries with missing or invalid data
                    if (!isset($entry['PlTg']) || !isset($entry['PlSt'])) {
                        continue;
                    }

                    $tag = (int) $entry['PlTg'];
                    $stunde = (int) $entry['PlSt'];

                    // Validate ranges
                    if ($tag >= 1 && $tag <= 5 && $stunde >= 1 && $stunde <= 6) {
                        $timetable[$tag][$stunde] = $entry;
                    }
                }
                break;
            }
        }

        return $timetable;
    }

    /**
     * Build timetable array for a specific teacher
     */
    private function buildTimetableForTeacher($data, $teacherName)
    {
        $timetable = [];

        // Initialize empty timetable
        for ($tag = 1; $tag <= 5; $tag++) {
            for ($stunde = 1; $stunde <= 6; $stunde++) {
                $timetable[$tag][$stunde] = [];
            }
        }

        // Collect all entries first
        $tempEntries = [];

        // Search through all classes
        foreach ($data['Klassen'] as $klasse) {
            if (!isset($klasse['Plan']) || !is_array($klasse['Plan'])) {
                continue;
            }

            foreach ($klasse['Plan'] as $entry) {
                // Check if this entry includes the teacher
                if (!isset($entry['PlLe']) || !is_array($entry['PlLe'])) {
                    continue;
                }

                if (!in_array($teacherName, $entry['PlLe'])) {
                    continue;
                }

                // Skip entries with missing or invalid data
                if (!isset($entry['PlTg']) || !isset($entry['PlSt'])) {
                    continue;
                }

                $tag = (int) $entry['PlTg'];
                $stunde = (int) $entry['PlSt'];

                // Validate ranges
                if ($tag >= 1 && $tag <= 5 && $stunde >= 1 && $stunde <= 6) {
                    // Add class information to entry
                    $entryWithClass = $entry;
                    $entryWithClass['KlassenInfo'] = $klasse['Kurzform'];

                    // Store in temporary array
                    if (!isset($tempEntries[$tag][$stunde])) {
                        $tempEntries[$tag][$stunde] = [];
                    }
                    $tempEntries[$tag][$stunde][] = $entryWithClass;
                }
            }
        }

        // Group entries by room and subject
        foreach ($tempEntries as $tag => $stunden) {
            foreach ($stunden as $stunde => $entries) {
                $grouped = [];

                foreach ($entries as $entry) {
                    // Create a key based on room and subject
                    $raum = implode(',', $entry['PlRa'] ?? []);
                    $fach = $entry['PlFa'] ?? '';
                    $key = $raum . '|' . $fach;

                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'PlTg' => $entry['PlTg'],
                            'PlSt' => $entry['PlSt'],
                            'PlFa' => $entry['PlFa'] ?? '',
                            'PlLe' => $entry['PlLe'] ?? [],
                            'PlRa' => $entry['PlRa'] ?? [],
                            'PlKl' => [],
                            'KlassenInfo' => [],
                        ];
                    }

                    // Add class to grouped entry
                    if (isset($entry['KlassenInfo'])) {
                        $grouped[$key]['KlassenInfo'][] = $entry['KlassenInfo'];
                    }

                    // Merge PlKl if exists
                    if (isset($entry['PlKl']) && is_array($entry['PlKl'])) {
                        $grouped[$key]['PlKl'] = array_merge($grouped[$key]['PlKl'], $entry['PlKl']);
                    }
                }

                // Add grouped entries to timetable
                foreach ($grouped as $groupedEntry) {
                    // Remove duplicates from class lists
                    $groupedEntry['KlassenInfo'] = array_unique($groupedEntry['KlassenInfo']);
                    $groupedEntry['PlKl'] = array_unique($groupedEntry['PlKl']);

                    $timetable[$tag][$stunde][] = $groupedEntry;
                }
            }
        }

        return $timetable;
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

    /**
     * Get dummy substitutions data
     */
    private function getDummyVertretungen($class, $startDate)
    {
        $vertretungen = collect();

        // Only for class "1Frue" - add dummy substitutions
        if ($class === '1Frue') {
            // Monday (Tag 1), Stunde 3: Math substitution
            $vertretungen->push((object)[
                'date' => $startDate->copy()->addDays(0), // Monday
                'klasse' => '1Frue',
                'stunde' => 3,
                'altFach' => 'MA',
                'neuFach' => 'DE',
                'lehrer' => 'Schu (Vertretung)',
                'comment' => 'Wal ist erkrankt',
            ]);

            // Wednesday (Tag 3), Stunde 5: Art cancelled
            $vertretungen->push((object)[
                'date' => $startDate->copy()->addDays(2), // Wednesday
                'klasse' => '1Frue',
                'stunde' => 5,
                'altFach' => 'KU',
                'neuFach' => 'Selbststudium',
                'lehrer' => null,
                'comment' => 'Raumwechsel - Selbststudium im Klassenzimmer',
            ]);
        }

        return $vertretungen->groupBy(function($item) {
            return Carbon::parse($item->date)->dayOfWeekIso;
        });
    }

    /**
     * Get dummy news data
     */
    private function getDummyNews()
    {
        return [
            (object)[
                'news' => 'Am Freitag findet die Schulversammlung in der 3. und 4. Stunde statt.',
                'start' => Carbon::now(),
                'end' => Carbon::now()->addDays(3),
            ],
            (object)[
                'news' => 'Nächste Woche (A-Woche) beginnt das Schwimmprojekt für die Klassen 1-3.',
                'start' => Carbon::now(),
                'end' => Carbon::now()->addWeek(),
            ],
        ];
    }

    /**
     * Get dummy absences data
     */
    private function getDummyAbsences()
    {
        return [
            (object)[
                'name' => 'Ma',
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(3),
                'reason' => 'Erkrankt',
            ],
            (object)[
                'name' => 'Wal',
                'start_date' => Carbon::now()->subDay(),
                'end_date' => Carbon::now()->addDay(),
                'reason' => 'Fortbildung',
            ],
        ];
    }

    /**
     * Show import form
     */
    public function showImport()
    {
        $stundenplanSettings = app(StundenplanSetting::class);

        if (!$stundenplanSettings->allow_web_import) {
            abort(403, 'Web-Import ist nicht aktiviert');
        }

        $currentData = null;
        if (Storage::disk('local')->exists('stundenplan/current.json')) {
            $content = Storage::disk('local')->get('stundenplan/current.json');
            $currentData = json_decode($content, true);
        }

        return view('stundenplan.import', [
            'stundenplanSettings' => $stundenplanSettings,
            'currentData' => $currentData,
        ]);
    }

    /**
     * Process import via web
     */
    public function processImport(Request $request)
    {
        $settings = app(StundenplanSetting::class);

        if (!$settings->allow_web_import) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Web-Import ist nicht aktiviert',
            ]);
        }

        $request->validate([
            'json_file' => 'required|file|mimes:json|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('json_file');
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (!$data) {
                throw new \Exception('Ungültige JSON-Datei');
            }

            // Normalize data format (supports both direct and Indiware export format)
            $normalizedData = StundenplanDataAdapter::normalize($data);

            // Validate normalized data
            if (!StundenplanDataAdapter::validate($normalizedData)) {
                throw new \Exception('Datenvalidierung fehlgeschlagen. Erforderliche Felder fehlen.');
            }

            // Import to database
            $importer = new StundenplanDatabaseImporter();
            $importStats = $importer->import($normalizedData);

            // Store with timestamp (backup)
            $filename = 'stundenplan/import_' . date('Y-m-d_H-i-s') . '.json';
            Storage::disk('local')->put($filename, json_encode($normalizedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Store as current
            Storage::disk('local')->put('stundenplan/current.json', json_encode($normalizedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Clear cache
            Cache::forget('stundenplan_data');
            Cache::forget('stundenplan_data_from_db');

            Log::info('Stundenplan imported via Web-UI', array_merge([
                'filename' => $filename,
                'user_id' => auth()->id(),
            ], $importStats));

            return redirect()->back()->with([
                'type' => 'success',
                'Meldung' => sprintf(
                    'Stundenplan erfolgreich importiert: %d Klassen, %d Zeitslots, %d Einträge in Datenbank gespeichert',
                    $importStats['klassen'],
                    $importStats['zeitslots'],
                    $importStats['eintraege']
                ),
            ]);

        } catch (\Exception $e) {
            Log::error('Stundenplan import failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Import fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }
}
