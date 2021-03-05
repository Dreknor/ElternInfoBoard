<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateListeRequest;
use App\Model\Group;
use App\Model\Liste;
use App\Repositories\GroupsRepository;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            $listen = Liste::where('ende', '>=', Carbon::now()->subWeeks(2))->get();
        } else {
            $listen = $request->user()->listen()->where('active', 1)->where('ende', '>=', Carbon::now())->get();
            if ($request->user()->can('create terminliste')) {
                $eigeneListen = Liste::where('besitzer', $request->user()->id)->where('ende', '>=', Carbon::now()->subWeeks(2))->get();

                $listen = $listen->merge($eigeneListen);
            }
        }

        $listen = $listen->unique('id');
        $eintragungen = $request->user()->listen_eintragungen;

        if ($request->user()->sorg2 != null) {
            $eintragungen = $eintragungen->merge($request->user()->sorgeberechtigter2->listen_eintragungen);
        }

        return view('listen.index', [
            'listen' => $listen,
            'eintragungen'  => $eintragungen,
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
            'gruppen'   => Group::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return RedirectResponse
     */
    public function store(CreateListeRequest $request)
    {
        $this->authorize('create', Liste::class);

        $Liste = new Liste($request->all());
        //$Liste->active = 0;
        $Liste->besitzer = $request->user()->id;

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
        $terminListe->load('eintragungen');
        $terminListe->eintragungen->sortBy('termin');

        if ($terminListe->type == 'termin') {
            return view('listen.terminAuswahl', [
                'liste' => $terminListe,
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TerminListe  $terminListe
     * @return View
     */
    public function edit(Liste $terminListe)
    {
        $this->authorize('editListe', $terminListe);

        return view('listen.edit', [
            'liste'    => $terminListe,
            'gruppen'=> Group::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\TerminListe  $terminListe
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Liste $terminListe)
    {
        $this->authorize('editListe', $terminListe);

        $terminListe->update($request->all());

        $gruppen = $request->input('gruppen');
        $gruppen = $this->grousRepository->getGroups($gruppen);

        $terminListe->groups()->detach();
        $terminListe->groups()->attach($gruppen);

        return redirect(url('listen'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\TerminListe  $terminListe
     * @return \Illuminate\Http\Response
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
        if ($request->user()->id == $liste->besitzer or $request->user()->can('edit terminlisten')) {
            /*$pdf = \PDF::loadView('listen.listenExport', [
                "Liste" => $liste,
                'listentermine' => $liste->eintragungen->sortBy('termin')
            ]);
            return $pdf->download('test.pdf');
            */
            return view('listen.listenExport', [
                'Liste' => $liste,
                'listentermine' => $liste->eintragungen->sortBy('termin'),
            ]);
        }

        return redirect()->back()->with([
           'type'   => 'error',
           'Meldung'=>  'Berechtigung fehlt',
        ]);
    }
}
