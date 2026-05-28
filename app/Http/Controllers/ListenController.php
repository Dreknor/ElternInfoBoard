<?php

namespace App\Http\Controllers;

use App\Exports\ListenExport;
use App\Http\Requests\CreateListeRequest;
use App\Http\Requests\UpdateListenRequest;
use App\Model\Group;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Repositories\GroupsRepository;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class ListenController extends Controller
{
    private GroupsRepository $grousRepository;

    public function __construct(GroupsRepository $groupsRepository)
    {
        $this->grousRepository = $groupsRepository;
    }

    public function search(Request $request)
    {

        if (! $request->user()->can('edit terminliste')) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        // Bei POST-Request die Suchanfrage in Session speichern
        if ($request->isMethod('post')) {
            $query = $request->input('query');
            session(['listen_search_query' => $query]);
        } else {
            // Bei GET-Request (Paginierung) die Suchanfrage aus Session holen
            $query = session('listen_search_query', '');
        }

        $archiv = Liste::where('ende', '<', now())
            ->where('listenname', 'LIKE', "%{$query}%")
            ->paginate(10);

        return view('listen.search', [
            'archiv' => $archiv,
            'query' => $query,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     *
     *
     */
    public function index(Request $request)
    {


        if ($request->user()->can('edit terminliste')) {
            $listen = Liste::where('ende', '>=', Carbon::today())->get();
            $oldListen = Liste::where('ende', '<', Carbon::today())->orderByDesc('ende')->paginate(15);
        } else {
            $oldListen = '';
            $listen = $request->user()->listen()->where('active', 1)->where('ende', '>=', Carbon::now())->get();
            if ($request->user()->can('create terminliste')) {
                $eigeneListen = Liste::where('besitzer', $request->user()->id)->where('ende', '>=', Carbon::now())->get();

                $listen = $listen->merge($eigeneListen);
            }
        }

        $listen = $listen->unique('id');

        if (auth()->user()->sorg2 == null) {
            $eintragungen = Listen_Eintragungen::query()
                ->where('user_id', auth()->id())
                ->get();
        } else {
            $eintragungen = Listen_Eintragungen::query()
                ->where('user_id', auth()->id())
                ->orWhere('user_id', auth()->user()->sorg2)
                ->get();
        }

        $termine = auth()->user()->getListenTermine();

        return view('listen.index', [
            'listen' => $listen,
            'eintragungen' => $eintragungen,
            'termine' => $termine,
            'archiv' => $oldListen,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     *
     * @throws AuthorizationException
     */
    public function create()
    {
        if (!auth()->user()->can('create terminliste')) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        return view('listen.create', [
            'gruppen' => Group::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function store(CreateListeRequest $request)
    {

        if (!auth()->user()->can('create terminliste')) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $gruppen = $request->input('gruppen');
        if (is_null($gruppen)) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Es muss mindestens eine Gruppe ausgewählt werden.',
            ]);
        }

        $Liste = new Liste($request->validated());
        // $Liste->active = 0;
        $Liste->besitzer = auth()->id();

        $Liste->save();

        $gruppen = $this->grousRepository->getGroups($gruppen);
        $Liste->groups()->attach($gruppen);

        if ($Liste->active) {
            $Liste->notify(
                users: $Liste->users,
                title: 'Neue Liste erstellt',
                message: 'Es wurde eine die Liste '.$Liste->listenname.' veröffentlicht.',
                url: url('listen/'.$Liste->id),
                type: 'Listen'
            );
        }

        return redirect(url("listen/$Liste->id"));
    }

    /**
     * Display the specified resource.
     *
     * @return View
     */
    public function show(Liste $terminListe)
    {
        if (!auth()->user()->listen()->where('listen.id', $terminListe->id)->exists() and !auth()->user()->can('edit terminliste')) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        if ($terminListe->type == 'termin') {
            $terminListe->load('termine');
            $terminListe->termine->sortBy('termin');

            return view('listen.terminListen.terminAuswahl', [
                'liste' => $terminListe,
            ]);
        }

        $terminListe->load('eintragungen');

        return view('listen.listenEintrag', [
            'liste' => $terminListe,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return View
     *
     * @throws AuthorizationException
     */
    public function edit(Liste $terminListe)
    {
        if (!auth()->user()->can('edit terminliste')) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        return view('listen.edit', [
            'liste' => $terminListe,
            'gruppen' => Group::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function update(UpdateListenRequest $request, Liste $terminListe)
    {
        if (!auth()->user()->can('edit terminliste')) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $terminListe->update($request->validated());

        $gruppen = $request->input('gruppen');
        $gruppen = $this->grousRepository->getGroups($gruppen);

        $terminListe->groups()->detach();
        $terminListe->groups()->attach($gruppen);

        return redirect()->to(url('listen'));
    }

    /**
     * Veröffentlicht die Liste
     *
     * @return RedirectResponse
     */
    public function activate($liste)
    {
        if (!auth()->user()->can('edit terminliste') and !$liste->besitzer == auth()->id()) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);

        }

        $liste = Liste::find($liste);
        $liste->update([
            'active' => 1,
        ]);

        return redirect()->back();
    }

    /**
     * Liste ausblenden
     *
     * @return RedirectResponse
     */
    public function deactivate($liste)
    {
        if (!auth()->user()->can('edit terminliste') and !$liste->besitzer == auth()->id()) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);

        }

        $liste = Liste::find($liste);
        $liste->update([
            'active' => 0,
        ]);

        return redirect()->back();
    }

    /**
     *  Erstellt eine druckbare Ansicht im Browser
     *
     * @return View|RedirectResponse
     */
    public function pdf(Liste $liste)
    {
        if (auth()->user()->id == $liste->besitzer or auth()->user()->can('edit terminliste')) {

            if ($liste->type == 'termin') {
                return view('listen.listenTerminExport', [
                    'Liste' => $liste,
                    'listentermine' => $liste->termine->sortBy('termin'),
                ]);
            } else {
                return view('listen.listenEintragExport', [
                    'liste' => $liste,
                    'listentermine' => $liste->termine->sortBy('termin'),
                ]);
            }
        }

        return redirect()->back()->with([
            'type' => 'error',
            'Meldung' => 'Berechtigung fehlt',
        ]);
    }

    /**
     * Abgelaufene Liste verlängern um 2 Wochen
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function refresh(Liste $liste)
    {
        if (!auth()->user()->can('edit terminliste')) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $liste->update([
            'ende' => Carbon::now()->addWeeks(2),
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Liste verlängert',
        ]);
    }

    /**
     * Aktive Liste archivieren
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function archiv(Liste $liste)
    {
        if (!auth()->user()->can('edit terminliste')) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $liste->update([
            'ende' => Carbon::now()->subDay(),
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Liste beendet',
        ]);
    }

    /**
     * Exportiert alle Termine einer Terminliste als iCal-Datei.
     * Mit ?block=1 werden aufeinanderfolgende Termine zu einem Block zusammengefasst.
     *
     * @return \Illuminate\Http\Response|RedirectResponse
     */
    public function icalExport(Request $request, Liste $liste)
    {
        // Listenbesitzer oder Benutzer mit "edit terminliste"-Berechtigung
        if (auth()->user()->id != $liste->besitzer && ! auth()->user()->can('edit terminliste')) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        if ($liste->type != 'termin') {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Nur Terminlisten können exportiert werden',
            ]);
        }

        $termine = $liste->termine->sortBy('termin')->values();
        $mergeBlocks = $request->boolean('block', false);

        $icalObject = Calendar::create(config('app.name'))
            ->name($liste->listenname);

        if ($mergeBlocks && $termine->count() > 0) {
            // Aufeinanderfolgende Termine zu Blöcken zusammenfassen
            $blocks = [];
            $blockStart = null;
            $blockEnd = null;

            foreach ($termine as $termin) {
                $start = $termin->termin->copy()->timezone('Europe/Berlin');
                $end   = $termin->termin->copy()->addMinutes($termin->duration)->timezone('Europe/Berlin');

                if ($blockStart === null) {
                    $blockStart = $start;
                    $blockEnd   = $end;
                } elseif ($start->lte($blockEnd)) {
                    // Termin schließt direkt an oder überschneidet sich → Block verlängern
                    if ($end->gt($blockEnd)) {
                        $blockEnd = $end;
                    }
                } else {
                    // Lücke gefunden → Block abschließen, neuen starten
                    $blocks[] = ['start' => $blockStart, 'end' => $blockEnd];
                    $blockStart = $start;
                    $blockEnd   = $end;
                }
            }

            if ($blockStart !== null) {
                $blocks[] = ['start' => $blockStart, 'end' => $blockEnd];
            }

            foreach ($blocks as $block) {
                $icalObject->event(
                    Event::create()
                        ->name($liste->listenname)
                        ->uniqueIdentifier(uuid_create())
                        ->startsAt($block['start'])
                        ->endsAt($block['end'])
                );
            }
        } else {
            // Jeden Termin einzeln exportieren
            foreach ($termine as $termin) {
                if ($termin->reserviert_fuer != null) {
                    $name = $liste->listenname.' – '.$termin->eingetragenePerson?->name;
                } else {
                    $name = $liste->listenname;
                }

                $icalObject->event(
                    Event::create()
                        ->name($name)
                        ->uniqueIdentifier($termin->id ? (string) $termin->id : uuid_create())
                        ->startsAt($termin->termin->copy()->timezone('Europe/Berlin'))
                        ->endsAt($termin->termin->copy()->addMinutes($termin->duration)->timezone('Europe/Berlin'))
                        ->description($termin->comment ?? '')
                );
            }
        }

        $filename = Str::slug($liste->listenname, '-').'.ics';

        return response($icalObject->get())
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function exportExcelTermine($id)
    {
        if (! auth()->user()->can('edit terminliste')) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $liste = Liste::findOrFail($id);

        if ($liste->type == 'termin') {
            $listentermine = $liste->termine;
        } else {
            $listentermine = $liste->eintragungen;
        }

        return Excel::download(
            new ListenExport($listentermine, $liste),
            $liste->listenname.'.xlsx'
        );
    }
}
