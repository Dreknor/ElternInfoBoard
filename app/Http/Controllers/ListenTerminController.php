<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListeTerminRequest;
use App\Mail\TerminAbsage;
use App\Mail\TerminAbsageEltern;
use App\Model\Liste;
use App\Model\listen_termine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Class ListenTerminController
 * @package App\Http\Controllers
 */
class ListenTerminController extends Controller
{
    /**
     * Speichert verfügbare Termine
     * @param Liste $liste
     * @param StoreListeTerminRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
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

    /**
     * @param listen_termine $listen_termine
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(listen_termine $listen_termine){

        $Eintragungen = auth()->user()->listen_eintragungen()->where('listen_id', $listen_termine->liste->id)->get();

        if (count($Eintragungen)>0 and $listen_termine->liste->multiple != 1){
            return redirect()->back()->with([
               'type'   => 'warning',
               'Meldung'    => "Es kann nur ein Termin reserviert werden"
            ]);
        }

        $listen_termine->update([
            "reserviert_fuer" => auth()->user()->id
        ]);

        return redirect(url('listen'))->with([
            "type"  => 'success',
            'Meldung'   => "Termin wurde reserviert."
        ]);
    }

    /**
     * @param listen_termine $listen_termine
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(listen_termine $listen_termine){

        if (auth()->user()->id == $listen_termine->reserviert_fuer or $listen_termine->reserviert_fuer == auth()->user()->sorg2){
            Mail::to($listen_termine->liste->ersteller->email, $listen_termine->liste->ersteller->name)
                ->queue(new TerminAbsageEltern(auth()->user(),$listen_termine->liste, $listen_termine->termin));

            $listen_termine->update(["reserviert_fuer" => null]);

            return redirect()->back()->with([
                'type'  => "success",
                'Meldung'=> "Termin abgesagt"
            ]);
        }

        if (auth()->user()->id == $listen_termine->liste->besitzer or auth()->user()->can('edit terminliste')){

            if ($listen_termine->reserviert_fuer != null){
                //E-Mail versenden
                Mail::to($listen_termine->eingetragenePerson->email,$listen_termine->eingetragenePerson->name)
                    ->queue(new TerminAbsage($listen_termine->eingetragenePerson->name,$listen_termine->liste, $listen_termine->termin, auth()->user() ));
                $listen_termine->update([
                    'reserviert_fuer'   => null
                ]);
            } else {
                $listen_termine->delete();
            }

            return redirect()->back()->with([
                'type'  => "success",
                'Meldung'=> "Termin gelöscht bzw. abgesagt"
            ]);

        }

        return redirect()->back()->with([
            'type'  => "danger",
            'Meldung'=> "Keine Recht den Termin abzusagen?"
        ]);
    }
}
