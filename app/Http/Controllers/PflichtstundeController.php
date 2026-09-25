<?php

namespace App\Http\Controllers;

use App\Exports\PflichtstundenExport;
use App\Http\Requests\CreatePflichtstundeRequest;
use App\Http\Requests\UpdatePflichtstundeRequest;
use App\Model\Pflichtstunde;
use App\Model\User;
use App\Services\Pflichtstunden\PflichtstundenService;
use App\Settings\PflichtstundenSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Maatwebsite\Excel\Facades\Excel;

class PflichtstundeController extends Controller implements HasMiddleware
{
    protected PflichtstundenSetting $pflichtstunden_settings;

    public function __construct()
    {

        $this->pflichtstunden_settings = new PflichtstundenSetting;
    }

    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('view Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $user = auth()->user();
        $service = app(PflichtstundenService::class);
        $parent_stats = $service->ranking($user);

        return view('pflichtstunden.index', [
            'pflichtstunden' => $service->entriesFor($user),
            'pflichtstunden_settings' => $this->pflichtstunden_settings,
            'parent_stats' => $parent_stats,
            'unit' => $parent_stats['unit'],
            'basisDescription' => $service->basisDescription(),
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreatePflichtstundeRequest $request)
    {
        $data = $request->validated();

        // Wenn user_id nicht gesetzt ist oder Nutzer keine Berechtigung hat, für andere anzulegen
        if (! isset($data['user_id']) || ! auth()->user()->can('edit Pflichtstunden')) {
            // Dann für den aktuell angemeldeten Nutzer
            $data['user_id'] = auth()->id();
        }

        Pflichtstunde::create($data);

        return redirect()->back()->with('success', 'Pflichtstunde angelegt');
    }

    /**
     * Verwaltungsansicht der Pflichtstunden
     */
    public function verwaltungIndex()
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $pflichtstunden = Pflichtstunde::query()
            ->where('approved', false)
            ->where('rejected', false)
            ->where('end', '<', now())
            ->orderBy('end', 'desc')
            ->get();

        $overview = app(PflichtstundenService::class)->overview();
        $groupedUsers = $overview['rows'];
        $stats = $overview['stats'];

        return view('pflichtstunden.indexVerwaltung', [
            'pflichtstunden' => $pflichtstunden,
            'pflichtstunden_settings' => $this->pflichtstunden_settings,
            'groupedUsers' => $groupedUsers,
            'allGroupedUsers' => $groupedUsers, // Für Select2
            'stats' => $stats,
        ]);
    }

    public function approve(Request $request, Pflichtstunde $pflichtstunde)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $currentId = $pflichtstunde->id;

        $pflichtstunde->approved = true;
        $pflichtstunde->approved_at = now();
        $pflichtstunde->approved_by = auth()->id();
        $pflichtstunde->rejected = false;
        $pflichtstunde->rejected_at = null;
        $pflichtstunde->rejected_by = null;
        $pflichtstunde->rejection_reason = null;
        $pflichtstunde->save();

        // Finde die nächste unbestätigte Pflichtstunde
        $nextPflichtstunde = Pflichtstunde::query()
            ->where('approved', false)
            ->where('rejected', false)
            ->where('end', '<', now())
            ->where('id', '>', $currentId)
            ->orderBy('id', 'asc')
            ->first();

        // URL-Parameter sammeln
        $params = [];
        if ($nextPflichtstunde) {
            $params['scroll_to'] = $nextPflichtstunde->id;
        }

        // Bereich-Filter übernehmen falls vorhanden
        if ($request->has('bereich_filter')) {
            $params['bereich_filter'] = $request->input('bereich_filter');
        }

        return redirect()->route('pflichtstunden.indexVerwaltung', $params)
            ->with('success', 'Pflichtstunde genehmigt');
    }

    public function approveMultiple(Request $request)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $ids = json_decode($request->input('ids'), true);

        if (empty($ids) || ! is_array($ids)) {
            return redirect()->route('pflichtstunden.indexVerwaltung')->with('error', 'Keine Pflichtstunden ausgewählt');
        }

        $count = Pflichtstunde::query()
            ->whereIn('id', $ids)
            ->where('approved', false)
            ->where('rejected', false)
            ->update([
                'approved' => true,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejected' => false,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]);

        return redirect()->route('pflichtstunden.indexVerwaltung')
            ->with('success', $count.' Pflichtstunde(n) wurden genehmigt');
    }

    public function reject(Request $request, Pflichtstunde $pflichtstunde)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
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

        // URL-Parameter sammeln
        $params = [];
        if ($request->has('bereich_filter')) {
            $params['bereich_filter'] = $request->input('bereich_filter');
        }

        return redirect()->route('pflichtstunden.indexVerwaltung', $params)
            ->with('success', 'Pflichtstunde abgelehnt');
    }

    /**
     * Excel-Export für Pflichtstunden-Abrechnung
     */
    public function export(Request $request)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $year = $request->get('year', null);

        return Excel::download(
            new PflichtstundenExport($year),
            'pflichtstunden_abrechnung_'.($year ?? 'aktuell').'_'.date('Y-m-d').'.xlsx'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePflichtstundeRequest $request, Pflichtstunde $pflichtstunde)
    {
        // Prüfe ob bereits bestätigt oder abgelehnt
        if ($pflichtstunde->approved || $pflichtstunde->rejected) {
            return redirect()->back()->with('error', 'Pflichtstunde kann nicht mehr bearbeitet werden, da sie bereits bestätigt oder abgelehnt wurde.');
        }

        $data = $request->validated();

        // user_id darf beim Update nicht geändert werden
        unset($data['user_id']);

        $pflichtstunde->update($data);

        return redirect()->back()->with('success', 'Pflichtstunde aktualisiert');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pflichtstunde $pflichtstunde)
    {
        // Prüfe Berechtigung: Entweder eigene Pflichtstunde (nicht bestätigt/abgelehnt) oder edit Pflichtstunden Berechtigung
        if (! auth()->user()->can('edit Pflichtstunden') &&
            ($pflichtstunde->user_id !== auth()->id() || $pflichtstunde->approved || $pflichtstunde->rejected)) {
            return redirect()->back()->with('error', 'Berechtigung fehlt oder Pflichtstunde kann nicht mehr gelöscht werden.');
        }

        // Zusätzliche Prüfung für normale User
        if (! auth()->user()->can('edit Pflichtstunden') && ($pflichtstunde->approved || $pflichtstunde->rejected)) {
            return redirect()->back()->with('error', 'Pflichtstunde kann nicht mehr gelöscht werden, da sie bereits bestätigt oder abgelehnt wurde.');
        }

        $pflichtstunde->delete();

        return redirect()->back()->with('success', 'Pflichtstunde gelöscht');
    }
}
