<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateListeRequest;
use App\Model\Groups;
use App\Model\Liste;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ListenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', Liste::class);


        if (auth()->user()->can('edit listen')){
            $listen = Liste::where('ende', '>=', Carbon::now()->subMonths(3));
        } else {
            $listen = auth()->user()->listen;
            if (auth()->user()->can('create terminliste')){
                $eigeneListen = Liste::where('besitzer', auth()->user()->id)->get();

                $listen = $listen->merge($eigeneListen);
            }
        }



        return view('listen.index', [
            'listen' => $listen,
            "eintragungen"  => auth()->user()->listen_eintragungen
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Liste::class);

        return view('listen.create', [
            "gruppen"   => Groups::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateListeRequest $request)
    {

        $this->authorize('create', Liste::class);


        $Liste = new Liste($request->all());
        $Liste->besitzer = auth()->user()->id;

        $Liste->save();

        $gruppen= $request->input('gruppen');

        if ($gruppen[0] == "all"){
            $gruppen = Groups::all();
        } elseif ($gruppen[0] == 'Grundschule' or $gruppen[0] == 'Oberschule' ){
            $gruppen = Groups::whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen)->get();
            $gruppen = $gruppen->unique();
        } else {
            $gruppen = Groups::find($gruppen);
        }

        $Liste->groups()->attach($gruppen);


        return redirect(url("listen/$Liste->id"));

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Liste  $Liste
     * @return \Illuminate\Http\Response
     */
    public function show(Liste $terminListe)
    {
        $terminListe->load('eintragungen');
        $terminListe->eintragungen->sortBy('termin');

        if ($terminListe->type == "termin"){
            return view('listen.terminAuswahl', [
                'liste' => $terminListe
            ]);
        }
/*
        if((auth()->user()->groups()->whereIn('name',$terminListe->groups)->count() < 1 and !auth()->user()->can('edit terminliste') and auth()->user()->id != $terminListe->besitzer)){
            return redirect()->back()->with([
                "type"  => 'danger',
                'Meldung'   => "Berechtigung fehlt".$terminListe->besitzer
            ]);
        };

        if (!auth()->user()->can('edit terminliste') and auth()->user()->id != $terminListe->besitzer and (!$terminListe->active or $terminListe->ende->lessThan(Carbon::now()->startOfDay()))){
            return redirect()->back()->with([
                "type"  => 'warning',
                'Meldung'   => "Liste ist deaktiviert oder abgelaufen"
            ]);
        }

        return view('listen.show',[
            'liste' => $terminListe->load('eintragungen')
        ]);
*/

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TerminListe  $terminListe
     * @return \Illuminate\Http\Response
     */
    public function edit(Liste $terminListe)
    {
        //
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

}

