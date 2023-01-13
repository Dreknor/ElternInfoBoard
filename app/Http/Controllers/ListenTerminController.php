<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListeTerminRequest;
use App\Http\Requests\TerminabsageRequest;
use App\Mail\TerminAbsage;
use App\Mail\TerminAbsageEltern;
use App\Model\Liste;
use App\Model\listen_termine;
use App\Notifications\Push;
use App\Notifications\PushTerminAbsage;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Class ListenTerminController
 */
class ListenTerminController extends Controller
{
    /**
     * @param listen_termine $listen_termine
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function copy(listen_termine $listen_termine)
    {
        $this->authorize('storeTerminToListe', $listen_termine->liste);

        $new = $listen_termine->replicate();
        $new->reserviert_fuer = null;
        $new->save();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Termin kopiert',
        ]);
    }

    /**
     * Speichert verfügbare Termine
     *
     * @param  Liste  $liste
     * @param  StoreListeTerminRequest  $request
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function store(Liste $liste, StoreListeTerminRequest $request)
    {
        $this->authorize('storeTerminToListe', $liste);
        $datum = Carbon::createFromFormat('Y-m-d H:i', $request->termin.' '.$request->zeit);
        $termin = new listen_termine([
            'listen_id' => $liste->id,
            'termin' => $datum,
            'duration' => ($request->duration != '') ? $request->duration : $liste->duration,
            'comment' => $request->comment,

        ]);

        if ($request->weekly == 1) {
            for ($x = 1; $x < $request->repeat; $x++) {
                $newTermin = $termin->replicate();
                $newTermin->termin = $newTermin->termin->addWeeks($x);
                $newTermin->save();
            }
        } elseif ($request->weekly == 0 and $request->repeat > 1) {
            for ($x = 1; $x < $request->repeat; $x++) {
                $newTermin = $termin->replicate();
                $newTermin->termin = $newTermin->termin->addMinutes($x * $termin->duration);
                $newTermin->save();
            }
        }

        $termin->save();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Termin erstellt',
        ]);
    }

    /**
     *
     *
     * @param  listen_termine  $listen_termine
     * @return RedirectResponse|Redirector
     */
    public function update(Request $request, listen_termine $listen_termine)
    {
        $Eintragungen = $request->user()->getListenTermine()->where('listen_id', $listen_termine->liste->id);

        if (count($Eintragungen) > 0 and $listen_termine->liste->multiple != 1) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Es kann nur ein Termin reserviert werden',
            ]);
        }

        $listen_termine->update([
            'reserviert_fuer' => $request->user()->id,
        ]);

        Notification::send($listen_termine->liste->ersteller, new Push($listen_termine->liste->listenname.': Termin vergeben', $request->user()->name.' hat den Termin '.$listen_termine->termin->format('d.m.Y H:i').' reserviert.'));

        return redirect()->to(url('listen'))->with([
            'type' => 'success',
            'Meldung' => 'Termin wurde reserviert.',
        ]);
    }

    /**
     * @param  listen_termine  $listen_termine
     * @return RedirectResponse
     *
     * @throws Exception
     */
    public function absagen(TerminabsageRequest $request, listen_termine $listen_termine)
    {
        if ($request->user()->id == $listen_termine->reserviert_fuer or $listen_termine->reserviert_fuer == $request->user()->sorg2 or $request->user()->id == $listen_termine->liste->besitzer or $request->user()->can('edit terminliste')) {

            //Email an Listenersteller
            Mail::to($listen_termine->liste->ersteller->email, $listen_termine->liste->ersteller->name)
                ->queue(new TerminAbsageEltern($request->user(),
                    $listen_termine->liste,
                    $listen_termine->termin,
                    $request->text));

            //Email an eingetragene Person
            Mail::to($listen_termine->eingetragenePerson->email, $listen_termine->eingetragenePerson->name)
                            ->queue(new TerminAbsageEltern($request->user(),
                                $listen_termine->liste,
                                $listen_termine->termin,
                                $request->text));

            $listen_termine->update(['reserviert_fuer' => null]);

            return redirect()->back()->with([
                'type' => 'success',
                'Meldung' => 'Termin abgesagt',
            ]);
        }

        return redirect()->back()->with([
            'type' => 'danger',
            'Meldung' => 'Keine Recht den Termin abzusagen?',
        ]);
    }

    /**
     * @param Request $request
     * @param listen_termine $listen_termine
     * @return RedirectResponse
     */
    public function destroy(Request $request, listen_termine $listen_termine)
    {
        if ($request->user()->id == $listen_termine->liste->besitzer or $request->user()->can('edit terminliste')) {
            if ($listen_termine->reserviert_fuer != null) {
                //WebPush
                $user = $listen_termine->eingetragenePerson;
                if ($user->sorg2 != '' and $user->sorg2 != null) {
                    $sorg2 = $user->sorgeberechtigter2;
                }

                $users = collect([$user, $request->user()]);
                if (! is_null($sorg2)) {
                    $users->push($sorg2);
                }

                $body = $listen_termine->liste->listenname.': Termin am '.$listen_termine->termin->format('d.m.Y H:i').' wurde abgesagt.';
                Notification::send($users, new PushTerminAbsage($body));

                //E-Mail versenden
                Mail::to($listen_termine->eingetragenePerson->email, $listen_termine->eingetragenePerson->name)
                    ->queue(new TerminAbsage($listen_termine->eingetragenePerson->name, $listen_termine->liste, $listen_termine->termin, $request->user()));
                $listen_termine->update([
                    'reserviert_fuer' => null,
                ]);
            } else {
                $listen_termine->delete();
            }

            return redirect()->back()->with([
                'type' => 'success',
                'Meldung' => 'Termin gelöscht bzw. abgesagt',
            ]);
        }

        return redirect()->back()->with([
            'type' => 'danger',
            'Meldung' => 'Keine Recht den Termin abzusagen?',
        ]);
    }
}
