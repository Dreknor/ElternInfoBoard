<?php

namespace App\Http\Controllers;

use App\Http\Requests\KrankmeldungRequest;
use App\Mail\krankmeldung;
use App\Model\krankmeldungen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class KrankmeldungenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {

        $krankmeldungen = auth()->user()->krankmeldungen->sortByDesc('ende');

            return view('krankmeldung.index', [
                'krankmeldungen' => $krankmeldungen
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    public function store(KrankmeldungRequest $request)
    {

        $krankmeldung = new krankmeldungen();
        $krankmeldung->fill($request->validated());
        $krankmeldung->users_id = auth()->id();
        $krankmeldung->save();

        Mail::to('info@esz-radebeul.de')
            ->cc(auth()->user()->email)
            ->queue(new krankmeldung(auth()->user()->email, auth()->user()->name, $request->name, Carbon::createFromFormat('Y-m-d',$request->start)->format('d.m.Y'), Carbon::createFromFormat('Y-m-d',$request->ende)->format('d.m.Y'), $request->kommentar));

        return redirect()->back()->with([
            'type' => "success",
            'Meldung'   => "Krankmeldung wurde gespeichert"
        ]);

    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\krankmeldungen  $krankmeldungen
     * @return \Illuminate\Http\Response
     */
    public function destroy(krankmeldungen $krankmeldungen)
    {
        //
    }
}
