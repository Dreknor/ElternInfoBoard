<?php

namespace App\Http\Controllers;

use App\Http\Requests\KrankmeldungExpressRequest;
use App\Model\Child;
use App\Model\Krankmeldungen;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Schnellerfassung von Krankmeldungen für das Sekretariat.
 *
 * Ermöglicht eine tastaturoptimierte Erfassung telefonisch eingehender
 * Krankmeldungen ohne Medienbruch: Live-Suche nach Kindern, Auswahl per
 * Tastatur und Speichern ohne E-Mail-Versand.
 */
class KrankmeldungExpressController extends Controller
{
    /**
     * Zeigt die Express-Erfassungsmaske inkl. aktuell krankgemeldeter Kinder an.
     */
    public function index(): View
    {
        return view('krankmeldung.express', [
            'krankmeldungen' => $this->currentKrankmeldungen(),
        ]);
    }

    /**
     * Liefert die aktuell krankgemeldeten Kinder als HTML-Fragment,
     * damit die Liste nach dem Speichern per AJAX aktualisiert werden kann,
     * ohne die komplette Seite neu zu laden.
     */
    public function currentList(): View
    {
        return view('krankmeldung.partials.express-current-list', [
            'krankmeldungen' => $this->currentKrankmeldungen(),
        ]);
    }

    /**
     * Alle Kinder, die für den heutigen Tag krankgemeldet sind.
     */
    private function currentKrankmeldungen()
    {
        $today = Carbon::today()->format('Y-m-d');

        return Krankmeldungen::query()
            ->with(['child.group:id,name', 'child.class:id,name', 'user:id,name'])
            ->whereDate('start', '<=', $today)
            ->whereDate('ende', '>=', $today)
            ->orderBy('start')
            ->get();
    }

    /**
     * Live-Suche nach Kindern anhand von Vorname, Nachname, Klasse oder Gruppe.
     * Mehrere Suchbegriffe (z. B. "max 3b") werden per Leerzeichen getrennt
     * und müssen jeweils auf mindestens eines der Felder passen.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->get('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $terms = array_filter(preg_split('/\s+/', $query));

        $children = Child::query()
            ->with(['group:id,name', 'class:id,name'])
            ->where(function ($builder) use ($terms) {
                foreach ($terms as $term) {
                    $builder->where(function ($termBuilder) use ($term) {
                        $termBuilder->where('first_name', 'like', '%'.$term.'%')
                            ->orWhere('last_name', 'like', '%'.$term.'%')
                            ->orWhereHas('group', function ($groupQuery) use ($term) {
                                $groupQuery->where('name', 'like', '%'.$term.'%');
                            })
                            ->orWhereHas('class', function ($classQuery) use ($term) {
                                $classQuery->where('name', 'like', '%'.$term.'%');
                            });
                    });
                }
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(8)
            ->get();

        $today = Carbon::today()->format('Y-m-d');

        $result = $children->map(function (Child $child) use ($today) {
            $sickToday = Krankmeldungen::query()
                ->where('child_id', $child->id)
                ->whereDate('start', '<=', $today)
                ->whereDate('ende', '>=', $today)
                ->exists();

            return [
                'id' => $child->id,
                'first_name' => $child->first_name,
                'last_name' => $child->last_name,
                'group' => $child->group?->name,
                'class' => $child->class?->name,
                'sick_today' => $sickToday,
            ];
        });

        return response()->json($result->values());
    }

    /**
     * Speichert eine neue Krankmeldung für ein Kind (ohne E-Mail-Versand).
     */
    public function store(KrankmeldungExpressRequest $request): JsonResponse
    {
        $child = Child::with(['group:id,name', 'class:id,name'])->find($request->child_id);

        if (! $child) {
            return response()->json(['message' => 'Das angegebene Kind wurde nicht gefunden.'], 404);
        }

        // Duplikats-Schutz: Überschneidet sich der gewählte Zeitraum mit einer
        // bereits bestehenden Krankmeldung für dieses Kind?
        $overlapping = Krankmeldungen::query()
            ->where('child_id', $child->id)
            ->whereDate('start', '<=', $request->ende)
            ->whereDate('ende', '>=', $request->start)
            ->exists();

        if ($overlapping) {
            return response()->json([
                'message' => 'Für dieses Kind besteht im gewählten Zeitraum bereits eine Krankmeldung.',
            ], 409);
        }

        try {
            $krankmeldung = new Krankmeldungen;
            $krankmeldung->child_id = $child->id;
            $krankmeldung->name = trim($child->first_name.' '.$child->last_name);
            $krankmeldung->start = $request->start;
            $krankmeldung->ende = $request->ende;
            $krankmeldung->kommentar = (string) $request->kommentar;
            $krankmeldung->users_id = auth()->id();
            $krankmeldung->save();

            Cache::forget('krankmeldung_'.$child->id);

            $label = $child->group?->name ?? $child->class?->name;

            return response()->json([
                'message' => trim($krankmeldung->name.($label ? ' ('.$label.')' : '')).' für '.
                    ($request->start === $request->ende
                        ? 'heute'
                        : Carbon::createFromFormat('Y-m-d', $request->start)->format('d.m.Y').' bis '.Carbon::createFromFormat('Y-m-d', $request->ende)->format('d.m.Y')
                    ).' krankgemeldet.',
                'child' => [
                    'id' => $child->id,
                    'name' => $krankmeldung->name,
                    'label' => $label,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Krankmeldung Express: Fehler beim Erstellen der Krankmeldung: '.$e->getMessage());

            return response()->json([
                'message' => 'Fehler beim Erstellen der Krankmeldung. Bitte versuchen Sie es erneut.',
            ], 500);
        }
    }
}
