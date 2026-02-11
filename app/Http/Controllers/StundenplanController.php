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

        return view('stundenplan.index', [
            'data' => $data,
            'currentView' => $view,
            'classes' => $this->extractClasses($data),
            'teachers' => $this->extractTeachers($data),
            'rooms' => $this->extractRooms($data),
        ]);
    }

    /**
     * Display the class view
     */
    public function klassenAnsicht(Request $request, $class)
    {
        if (!$request->user()->can('view stundenplan')) {
            return redirect(url('home'))->with([
                'type' => 'error',
                'Meldung' => 'Sie haben keine Berechtigung, den Stundenplan anzusehen.',
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
