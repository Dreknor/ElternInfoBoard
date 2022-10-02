<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateListeRequest;
use App\Model\Group;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Repositories\GroupsRepository;
use App\TerminListe;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ListenController extends Controller
{
    public function __construct(GroupsRepository $groupsRepository)
    {
        $this->grousRepository = $groupsRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Liste::class);

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
        $eintragungen = Listen_Eintragungen::query()->user(auth()->id())->orWhere->user(auth()->user()->sorg2)->get();
        $termine = listen_termine::query()->user(auth()->id())->orWhere->user(auth()->user()->sorg2)->get();

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
     */
    public function create()
    {
        $this->authorize('create', Liste::class);

        return view('listen.create', [
            'gruppen' => Group::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(CreateListeRequest $request)
    {
        $this->authorize('create', Liste::class);

        $Liste = new Liste($request->validated());
        //$Liste->active = 0;
        $Liste->besitzer = auth()->id();

        $Liste->save();

        $gruppen = $request->input('gruppen');
        $gruppen = $this->grousRepository->getGroups($gruppen);
        $Liste->groups()->attach($gruppen);

        return redirect(url("listen/$Liste->id"));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Liste  $Liste
     * @return View
     */
    public function show(Liste $terminListe)
    {
        if ($terminListe->type == 'termin') {
            $terminListe->load('termine');
            $terminListe->termine->sortBy('termin');

            return view('listen.terminAuswahl', [
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
     * @param  TerminListe  $terminListe
     * @return View
     */
    public function edit(Liste $terminListe)
    {
        $this->authorize('editListe', $terminListe);

        return view('listen.edit', [
            'liste' => $terminListe,
            'gruppen' => Group::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  TerminListe  $terminListe
     * @return Response
     */
    public function update(Request $request, Liste $terminListe)
    {
        $this->authorize('editListe', $terminListe);

        $terminListe->update($request->all());

        $gruppen = $request->input('gruppen');
        $gruppen = $this->grousRepository->getGroups($gruppen);

        $terminListe->groups()->detach();
        $terminListe->groups()->attach($gruppen);

        return redirect()->to(url('listen'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  TerminListe  $terminListe
     * @return Response
     */
    public function destroy(Liste $terminListe)
    {
        //
    }

    public function activate($liste)
    {
        $liste = Liste::find($liste);
        $liste->update([
            'active' => 1,
        ]);

        return redirect()->back();
    }

    public function deactivate($liste)
    {
        $liste = Liste::find($liste);
        $liste->update([
            'active' => 0,
        ]);

        return redirect()->back();
    }

    public function pdf(Request $request, Liste $liste)
    {
        if (auth()->user()->id == $liste->besitzer or auth()->user()->can('edit terminliste')) {
            /*$pdf = \PDF::loadView('listen.listenExport', [
                "Liste" => $liste,
                'listentermine' => $liste->eintragungen->sortBy('termin')
            ]);
            return $pdf->download('test.pdf');
            */

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

    public function refresh(Liste $liste)
    {
        $this->authorize('editListe', $liste);

        $liste->update([
            'ende' => Carbon::now()->addWeeks(2),
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Liste verlängert',
        ]);
    }

    public function archiv(Liste $liste)
    {
        $this->authorize('editListe', $liste);

        $liste->update([
            'ende' => Carbon::now()->subDay(),
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Liste beendet',
        ]);
    }
}
