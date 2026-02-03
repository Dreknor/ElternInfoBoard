<?php

namespace App\Http\Controllers;

use App\Model\ActiveDisease;
use App\Model\Losung;
use App\Model\Post;
use App\Model\Termin;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * Show the application dashboard.
     *
     * @return View
     */
    public function index()
    {
        // Hole nur die neuesten 5 Nachrichten

        if (auth()->user()->can('view all')) {
            $nachrichten = Post::query()
                ->where(function ($query) {
                    $query->whereNull('archiv_ab')
                        ->orWhere('archiv_ab', '>', Carbon::now());
                })
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $termine = Termin::query()
                ->where('start', '>=', Carbon::today())
                ->orderBy('start')
                ->take(5)
                ->get();

        } else {
            $nachrichten = Post::query()
                ->where('released', 1)
                ->where(function ($query) {
                    $query->whereNull('archiv_ab')
                        ->orWhere('archiv_ab', '>', Carbon::now());
                })
                ->whereHas('groups', function ($query) {
                    $query->whereIn('groups.id', auth()->user()->groups->pluck('id'));
                })
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $termine = Termin::query()
                ->where('start', '>=', Carbon::today())
                ->whereHas('groups', function ($query) {
                    $query->whereIn('groups.id', auth()->user()->groups->pluck('id'));
                })
                ->orderBy('start')
                ->take(5)
                ->get();
        }

        // Hole die heutige Losung
        $losung = Losung::whereDate('date', Carbon::today())->first();

        // Hole die Kinder des Benutzers, die den Care-Scope erfüllen
        $careChildren = auth()->user()->children_rel()
            ->care()
            ->orderBy('first_name')
            ->get();

        // Aktive meldepflichtige Erkrankungen abrufen
        $activeDiseases = Cache::remember('active_diseases', 60 * 5, function () {
            return ActiveDisease::query()
                ->where('active', true)
                ->whereDate('end', '>=', Carbon::now())
                ->with('disease')
                ->get();
        });

        // Prüfe auf offene Anwesenheitsabfragen für die Kinder des Benutzers
        $openAttendanceSurveys = false;
        if ($careChildren->count() > 0) {
            $childIds = $careChildren->pluck('id');

            $openSurveys = \App\Model\ChildCheckIn::query()
                ->whereIn('child_id', $childIds)
                ->where('should_be', false)
                ->where('checked_in', false)
                ->where('checked_out', false)
                ->where(function ($query) {
                    // Wenn lock_at gesetzt ist, prüfe ob es noch gültig ist
                    // Wenn lock_at nicht gesetzt ist, prüfe ob das Datum in der Zukunft liegt
                    $query->where(function ($q) {
                        $q->whereNotNull('lock_at')
                            ->where('lock_at', '>=', Carbon::today());
                    })->orWhere(function ($q) {
                        $q->whereNull('lock_at')
                            ->where('date', '>', Carbon::today());
                    });
                })
                ->count();

            $openAttendanceSurveys = $openSurveys > 0;
        }

        return view('dashboard.index', [
            'nachrichten' => $nachrichten,
            'termine' => $termine,
            'losung' => $losung,
            'datum' => Carbon::now(),
            'careChildren' => $careChildren,
            'activeDiseases' => $activeDiseases,
            'openAttendanceSurveys' => $openAttendanceSurveys,
        ]);
    }
}
