<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePflichtstundeRequest;
use App\Model\Pflichtstunde;
use App\Model\User;
use App\Settings\PflichtstundenSetting;
use Illuminate\Http\Request;
use App\Exports\PflichtstundenExport;
use Maatwebsite\Excel\Facades\Excel;

class PflichtstundeController extends Controller
{
    protected PflichtstundenSetting $pflichtstunden_settings;
    public function __construct()
    {
        $this->middleware('auth');
        $this->pflichtstunden_settings = new PflichtstundenSetting();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $pflichtstunden = auth()->user()->pflichtstunden;

        // Berechne Statistiken für Gamification
        $parent_stats = $this->calculateParentStats();

        return view('pflichtstunden.index', [
            'pflichtstunden' => $pflichtstunden,
            'pflichtstunden_settings' => $this->pflichtstunden_settings,
            'parent_stats' => $parent_stats
        ]);

    }

    /**
     * Berechne Statistiken für Ranking und Vergleich
     */
    private function calculateParentStats()
    {
        $currentUser = auth()->user();

        // Hole alle Nutzer mit Permission "view Pflichtstunden"
        $users = User::query()
            ->permission('view Pflichtstunden')
            ->with(['pflichtstunden' => function($query) {
                $query->where('approved', true);
            }])
            ->get();

        $requiredMinutes = $this->pflichtstunden_settings->pflichtstunden_anzahl * 60;
        $familyStats = collect();
        $processed = collect();

        // Gruppiere Nutzer als Familien (User + sorg2 Partner)
        foreach ($users as $user) {
            // Überspringe wenn bereits als sorg2 verarbeitet
            if ($processed->contains($user->id)) {
                continue;
            }

            // Berücksichtige auch den verknüpften Partner (sorg2)
            $totalMinutes = $user->pflichtstunden->sum('duration');
            $familyUserIds = [$user->id];

            if ($user->sorg2) {
                $partner = $users->where('id', $user->sorg2)->first();
                if ($partner) {
                    $totalMinutes += $partner->pflichtstunden->sum('duration');
                    $familyUserIds[] = $partner->id;
                    $processed->push($partner->id);
                }
            }

            $progress = $requiredMinutes > 0 ? min(100, round(($totalMinutes / $requiredMinutes) * 100, 2)) : 0;

            $familyStats->push([
                'user_ids' => $familyUserIds, // Alle User-IDs dieser Familie
                'name' => $user->name,
                'progress' => $progress,
                'total_minutes' => $totalMinutes,
            ]);

            $processed->push($user->id);
        }

        // Sortiere nach Fortschritt absteigend
        $familyStats = $familyStats->sortByDesc('progress')->values();

        // Berechne Fortschritt des aktuellen Nutzers
        $currentUserProgress = $currentUser->pflichtstunden->sum('duration');
        if ($currentUser->sorg2) {
            $partner = $users->where('id', $currentUser->sorg2)->first();
            if ($partner) {
                $currentUserProgress += $partner->pflichtstunden->sum('duration');
            }
        }
        $currentUserProgress = $requiredMinutes > 0 ? min(100, round(($currentUserProgress / $requiredMinutes) * 100, 2)) : 0;

        // Finde Rang des aktuellen Nutzers (schlechtester Rang bei Gleichstand)
        $userRank = 1;
        $currentRank = 1;
        $previousProgress = null;

        foreach ($familyStats as $index => $stat) {
            // Bei neuem Fortschritt-Wert wird der Rang auf die aktuelle Position gesetzt
            if ($previousProgress !== null && $stat['progress'] < $previousProgress) {
                $currentRank = $index + 1;
            }

            // Prüfe ob der aktuelle User in dieser Familie ist
            if (in_array($currentUser->id, $stat['user_ids'])) {
                $userRank = $currentRank;
                break;
            }

            $previousProgress = $stat['progress'];
        }

        // Berechne Durchschnitt
        $avgProgress = $familyStats->avg('progress');

        return [
            'total_parents' => $familyStats->count(),
            'your_rank' => $userRank,
            'avg_progress' => round($avgProgress, 2),
            'your_progress' => $currentUserProgress,
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreatePflichtstundeRequest $request)
    {
        $data = $request->validated();

        // Wenn user_id gesetzt ist und Nutzer die Berechtigung hat, für andere Nutzer Stunden anzulegen
        if (isset($data['user_id']) && auth()->user()->can('edit Pflichtstunden')) {
            // user_id bleibt wie übergeben
        } else {
            // Sonst für den aktuell angemeldeten Nutzer
            $data['user_id'] = auth()->id();
        }

        Pflichtstunde::create($data);

        return redirect()->back()->with('success', 'Pflichtstunde angelegt');
    }

    /**
     * Verwaltungsansicht der Pflichtstunden
     */

    public function verwaltungIndex(){
        if (!auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $pflichtstunden = Pflichtstunde::query()
            ->where('approved', false)
            ->where('rejected', false)
            ->with('user')->get();

        // Hole alle Nutzer mit Permission "view Pflichtstunden"
        $users = User::query()
            ->permission('view Pflichtstunden')
            ->with(['pflichtstunden' => function($query) {
                $query->where('approved', true);
            }])
            ->get();

        // Gruppiere nach Hauptnutzer (berücksichtige sorg2-Verknüpfung)
        $groupedUsers = collect();
        $processed = collect();

        // Statistiken initialisieren
        $stats = [
            'totalFamilies' => 0,
            'completed' => 0,
            'partial' => 0,
            'notStarted' => 0,
            'totalHoursCompleted' => 0,
            'totalHoursMissing' => 0,
            'totalHoursRequired' => 0,
            'totalBeitrag' => 0,
            'avgPercent' => 0,
        ];

        foreach ($users as $user) {
            // Überspringe wenn bereits als sorg2 verarbeitet
            if ($processed->contains($user->id)) {
                continue;
            }

            // Finde verknüpfte Person
            $partner = null;
            if ($user->sorg2) {
                $partner = $users->where('id', $user->sorg2)->first();
                if ($partner) {
                    $processed->push($partner->id);
                }
            }

            // Berechne kombinierte Statistiken
            $totalMinutes = $user->pflichtstunden->sum('duration');
            if ($partner) {
                $totalMinutes += $partner->pflichtstunden->sum('duration');
            }

            $requiredMinutes = $this->pflichtstunden_settings->pflichtstunden_anzahl * 60;
            $openMinutes = max(0, $requiredMinutes - $totalMinutes);

            // Berechne Beitrag
            $beitrag = 0;
            if ($openMinutes > 0) {
                $openHours = $openMinutes / 60;
                $beitrag = $openHours * $this->pflichtstunden_settings->pflichtstunden_betrag;
            }

            $percent = $requiredMinutes > 0 ? min(100, round(($totalMinutes / $requiredMinutes) * 100, 2)) : 0;

            $groupedUsers->push([
                'user' => $user,
                'partner' => $partner,
                'totalMinutes' => $totalMinutes,
                'openMinutes' => $openMinutes,
                'beitrag' => $beitrag,
                'percent' => $percent,
            ]);

            $processed->push($user->id);

            // Statistiken aktualisieren
            $stats['totalFamilies']++;
            $stats['totalHoursCompleted'] += $totalMinutes / 60;
            $stats['totalHoursMissing'] += $openMinutes / 60;
            $stats['totalHoursRequired'] += $requiredMinutes / 60;
            $stats['totalBeitrag'] += $beitrag;

            if ($percent >= 100) {
                $stats['completed']++;
            } elseif ($percent > 0) {
                $stats['partial']++;
            } else {
                $stats['notStarted']++;
            }
        }

        // Durchschnittliche Erfüllung berechnen
        if ($stats['totalFamilies'] > 0) {
            $stats['avgPercent'] = round($groupedUsers->avg('percent'), 2);
        }

        return view('pflichtstunden.indexVerwaltung', [
            'pflichtstunden' => $pflichtstunden,
            'pflichtstunden_settings' => $this->pflichtstunden_settings,
            'groupedUsers' => $groupedUsers,
            'allGroupedUsers' => $groupedUsers, // Für Select2
            'stats' => $stats,
        ]);
    }

    public function approve(Pflichtstunde $pflichtstunde)
    {
        if (!auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $pflichtstunde->approved = true;
        $pflichtstunde->approved_at = now();
        $pflichtstunde->approved_by = auth()->id();
        $pflichtstunde->rejected = false;
        $pflichtstunde->rejected_at = null;
        $pflichtstunde->rejected_by = null;
        $pflichtstunde->rejection_reason = null;
        $pflichtstunde->save();

        return redirect()->route('pflichtstunden.indexVerwaltung')->with('success', 'Pflichtstunde genehmigt');
    }

    public function reject(Request $request, Pflichtstunde $pflichtstunde)
    {
        if (!auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        $pflichtstunde->approved = false;
        $pflichtstunde->approved_at = null;
        $pflichtstunde->approved_by = null;
        $pflichtstunde->rejected = true;
        $pflichtstunde->rejected_at = now();
        $pflichtstunde->rejected_by = auth()->id();
        $pflichtstunde->rejection_reason = $request->input('rejection_reason');
        $pflichtstunde->save();

        return redirect()->route('pflichtstunden.indexVerwaltung')->with('success', 'Pflichtstunde abgelehnt');
    }

    /**
     * Excel-Export für Pflichtstunden-Abrechnung
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $year = $request->get('year', null);

        return Excel::download(
            new PflichtstundenExport($year),
            'pflichtstunden_abrechnung_' . ($year ?? 'aktuell') . '_' . date('Y-m-d') . '.xlsx'
        );
    }
}
