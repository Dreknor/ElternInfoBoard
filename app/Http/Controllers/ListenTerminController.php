<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListeTerminRequest;
use App\Http\Requests\TerminabsageRequest;
use App\Mail\TerminAbsage;
use App\Mail\TerminAbsageEltern;
use App\Model\Child;
use App\Model\Liste;
use App\Model\listen_termine;
use App\Model\User;
use App\Notifications\PushTerminAbsage;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Class ListenTerminController
 */
class ListenTerminController extends Controller
{
    /**
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function copy(listen_termine $listen_termine)
    {
        Gate::authorize('storeTerminToListe', $listen_termine->liste);

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
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function store(Liste $liste, StoreListeTerminRequest $request)
    {
        Gate::authorize('storeTerminToListe', $liste);
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
     * @return RedirectResponse|Redirector
     */
    public function update(Request $request, listen_termine $listen_termine)
    {
        if ($listen_termine->reserviert_fuer !== null) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Der Termin ist bereits vergeben.',
            ]);
        }

        // Kind der Buchung (Pflicht bei Listen je Kind, sonst optional – z. B. Elterngespräch)
        // und „nur ein Termin“ je Kind bzw. je Familie (gemeinsame, atomare Buchung mit der App-API)
        try {
            app(\App\Services\App\ListenService::class)->reserveTermin($request->user(), $listen_termine, $request->integer('child_id') ?: null);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => $e->getMessage(),
            ]);
        }

        return redirect()->to(url('listen'))->with([
            'type' => 'success',
            'Meldung' => 'Termin wurde reserviert.',
        ]);
    }

    /**
     * @return RedirectResponse
     *
     * @throws Exception
     */
    public function absagen(TerminabsageRequest $request, listen_termine $listen_termine)
    {
        if (app(\App\Services\App\ListenService::class)->mayCancel($request->user(), $listen_termine->reserviert_fuer ? (int) $listen_termine->reserviert_fuer : null, $listen_termine->child_id) or $request->user()->id == $listen_termine->liste->besitzer or $request->user()->can('edit terminliste')) {

            // Email an Listenersteller
            Mail::to($listen_termine->liste->ersteller->email, $listen_termine->liste->ersteller->name)
                ->queue(new TerminAbsageEltern($request->user(),
                    $listen_termine->liste,
                    $listen_termine->termin,
                    $request->text));

            // Email an eingetragene Person
            Mail::to($listen_termine->eingetragenePerson->email, $listen_termine->eingetragenePerson->name)
                ->queue(new TerminAbsageEltern($request->user(),
                    $listen_termine->liste,
                    $listen_termine->termin,
                    $request->text));

            $listen_termine->update(['reserviert_fuer' => null, 'child_id' => null]);

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
     * @return RedirectResponse
     */
    public function destroy(Request $request, listen_termine $listen_termine)
    {
        if ($request->user()->id == $listen_termine->liste->besitzer or $request->user()->can('edit terminliste')) {
            if ($listen_termine->reserviert_fuer != null) {
                // WebPush an die buchende Familie und den Absagenden
                $user = $listen_termine->eingetragenePerson;
                $users = User::query()->whereIn('id', $user->familyUserIds())->get()
                    ->push($request->user())
                    ->unique('id');

                $body = $listen_termine->liste->listenname.': Termin am '.$listen_termine->termin->format('d.m.Y H:i').' wurde abgesagt.';
                Notification::send($users, new PushTerminAbsage($body));

                // E-Mail versenden
                Mail::to($listen_termine->eingetragenePerson->email, $listen_termine->eingetragenePerson->name)
                    ->queue(new TerminAbsage($listen_termine->eingetragenePerson->name, $listen_termine->liste, $listen_termine->termin, $request->user()));
                $listen_termine->update([
                    'reserviert_fuer' => null,
                    'child_id' => null,
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
