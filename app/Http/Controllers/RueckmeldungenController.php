<?php

namespace App\Http\Controllers;

use App\Http\Requests\createRueckmeldungRequest;
use App\Model\Posts;
use App\Model\Rueckmeldungen;
use Illuminate\Http\Request;

class RueckmeldungenController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(createRueckmeldungRequest $request, $posts_id)
    {
        $rueckmeldung = new Rueckmeldungen($request->all());
        $rueckmeldung->posts_id = $posts_id;
        $rueckmeldung->save();

        return redirect(url('/home'))->with([
           "type"   => "success",
           "meldung"    => "Nachricht erstellt."
        ]);
    }

    public function edit(Rueckmeldungen $rueckmeldungen)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Rueckmeldungen  $rueckmeldungen
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $posts_id)
    {
        $rueckmeldung = Rueckmeldungen::firstOrNew([
            'posts_id'  => $posts_id
        ]);

        $rueckmeldung->fill($request->all());
        $rueckmeldung->save();

        return redirect(url('home'))->with([
           "type"   => "success",
           "meldung"    => "Rückmeldung gespeichert"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Rueckmeldungen  $rueckmeldungen
     * @return \Illuminate\Http\Response
     */
    public function destroy(Rueckmeldungen $rueckmeldung)
    {
        $rueckmeldung->delete();

        return response()->json([
            "message" => "Gelöscht".$rueckmeldung
        ], 200);
    }


}
