<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListeTerminRequest;
use App\Mail\TerminAbsage;
use App\Mail\TerminAbsageEltern;
use App\Model\Liste;
use App\Model\listen_termine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ListenTerminController extends Controller
{
    public function store(Liste $liste, StoreListeTerminRequest $request){

        $this->authorize('storeTerminToListe', $liste);
        $termin = new listen_termine([
            'listen_id' => $liste->id,
            'termin'    => Carbon::createFromFormat('Y-m-d\TH:i',$request->termin),
            "comment"   => $request->comment
        ]);

        if ($request->repeat > 1){
            for ($x =1; $x<$request->repeat; $x++){
                $newTermin = $termin->replicate();
                $newTermin->termin = $newTermin->termin->addMinutes($x*$liste->duration);
                $newTermin->save();
            }
        }

        $termin->save();

        return redirect()->back()->with([
            'type'  => "success",
            'Meldung'=> "Termin erstellt"
        ]);

    }

    public function update(listen_termine $listen_termine){

        $listen_termine->update([
            "reserviert_fuer" => auth()->user()->id
        ]);

        return redirect(url('listen'))->with([
            "type"  => 'success',
            'Meldung'   => "Termin wurde reserviert."
        ]);
    }

    public function destroy(listen_termine $listen_termine){

        if (auth()->user()->id == $listen_termine->liste->besitzer or auth()->user()->can('edit terminlisten')){

            if ($listen_termine->reserviert_fuer != null){
                //E-Mail versenden
                Mail::to($listen_termine->eingetragenePerson->email,$listen_termine->eingetragenePerson->name)
                    ->queue(new TerminAbsage($listen_termine->eingetragenePerson->name,$listen_termine->liste, $listen_termine->termin, auth()->user() ));
            }

            $listen_termine->delete();

            return redirect()->back()->with([
                'type'  => "success",
                'Meldung'=> "Termin gelöscht bzw. abgesagt"
            ]);

        }

        if (auth()->user()->id == $listen_termine->reserviert_fuer){
            Mail::to($listen_termine->liste->ersteller->email, $listen_termine->liste->ersteller->name)
                ->queue(new TerminAbsageEltern(auth()->user(),$listen_termine->liste, $listen_termine->termin));

            $listen_termine->update(["reserviert_fuer" => null]);

            return redirect()->back()->with([
                'type'  => "success",
                'Meldung'=> "Termin abgesagt"
            ]);
        }




        return redirect()->back()->with([
            'type'  => "danger",
            'Meldung'=> "Keine Recht den Termin abzusagen"
        ]);
    }
}
