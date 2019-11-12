<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateListeRequest;
use App\Model\TerminListe;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TerminListeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', TerminListe::class);

        if (auth()->user()->can('edit terminlisten')){
            $terminlisten = TerminListe::where('ende', '>=', Carbon::now()->subMonths(3));
        } else {
            $terminlisten = auth()->user()->terminlisten;
        }

        //$terminlisten->load('eintraege');

        return view('terminlisten.index', [
            'terminlisten' => $terminlisten
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', TerminListe::class);

        return view('terminlisten.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateListeRequest $request)
    {

        $Liste = new TerminListe($request->all());
        $Liste->besitzer = auth()->user()->id;

        $Liste->save();

        return redirect(url("listen/$Liste->id"));

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\TerminListe  $terminListe
     * @return \Illuminate\Http\Response
     */
    public function show(TerminListe $terminListe)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\TerminListe  $terminListe
     * @return \Illuminate\Http\Response
     */
    public function edit(TerminListe $terminListe)
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
    public function update(Request $request, TerminListe $terminListe)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\TerminListe  $terminListe
     * @return \Illuminate\Http\Response
     */
    public function destroy(TerminListe $terminListe)
    {
        //
    }
}
