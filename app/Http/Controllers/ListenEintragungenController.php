<?php

namespace App\Http\Controllers;

use App\Http\Requests\createListenEintragungsRequest;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListenEintragungenController extends Controller
{
    /**
     * @param  createListenEintragungsRequest  $request
     * @param  Liste  $liste
     * @return RedirectResponse
     */
    public function store(createListenEintragungsRequest $request, Liste $liste)
    {
        if ($liste->type != 'eintrag') {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'falscher Listen-Typ',
            ]);
        }

        $eintrag = new Listen_Eintragungen([
            'listen_id' => $liste->id,
            'eintragung' => $request->eintragung,
            'created_by' => auth()->id(),
        ]);

        if (! auth()->user()->can('edit terminliste')) {
            $eintrag->user_id = auth()->id();
        }

        $eintrag->save();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Eintrag erfolgreich',
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Listen_Eintragungen $listen_eintragung
     * @return RedirectResponse
     * @throws Throwable
     */
    public function update(Listen_Eintragungen $listen_eintragung)
    {
        if ($listen_eintragung->user_id == null) {
            $listen_eintragung->updateOrFail([
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with([
                'type' => 'success',
                'Meldung' => 'Eintrag gespeichert',
            ]);
        }

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Eintrag wurde nicht gespeichert',
        ]);
    }

    /**
     * @param  Listen_Eintragungen  $listen_eintragung
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function destroy(Listen_Eintragungen $listen_eintragung)
    {
        if (!$listen_eintragung){
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Eintrag nicht gefunden',
            ]);
        }
        $benachrichtigung = "";
        if (!is_null($listen_eintragung->user_id) and $listen_eintragung->user_id != auth()->id()) {


            try {
                $notification = new Notification([
                    'type' => 'Listen Eintragung',
                    'user_id' => $listen_eintragung->user->id,
                    'title' => 'Eintragung '.$listen_eintragung->eintragung.' wurde gelöscht',
                    'message' => 'Eintragung wurde von ' . auth()->user()->name . ' in der Liste ' . $listen_eintragung->liste->listenname . ' entfernt',
                    'icon' => 'https://eltern.esz-radebeul.de/img/favicon-esz.ico',
                    'url' => url('listen/'.$listen_eintragung->listen_id),
                ]);
                $notification->save();

                $benachrichtigung = "Benutzer wurde benachrichtigt";

            } catch (Throwable $e) {

                $benachrichtigung = "Benutzer konnte nicht benachrichtigt werden";
                Log::error($e->getMessage());
            }


            try {
                $listen_eintragung->user_id = null;
                $listen_eintragung->saveOrFail();
            } catch (Throwable $e) {
                Log::error($e->getMessage());
                return redirect()->back()->with([
                    'type' => 'error',
                    'Meldung' => 'Eintrag konnte nicht gelöscht werden',
                ]);
            }


            $listen_eintragung->updateOrFail([
                'user_id' => null,
            ]);

            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Eintrag wurde gelöscht. '.$benachrichtigung,
            ]);

        }

        if ($listen_eintragung->created_by == auth()->id() or auth()->user()->can('edit terminliste')) {
            $listen_eintragung->delete();
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Eintrag wurde gelöscht.',
            ]);
        }

        return redirect()->back()->with([
            'type' => 'error',
            'Meldung' => 'Eintrag konnte nicht gelöscht werden',
        ]);
    }
}
