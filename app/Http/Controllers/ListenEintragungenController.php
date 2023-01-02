<?php

namespace App\Http\Controllers;

use App\Http\Requests\createListenEintragungsRequest;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use Illuminate\Http\RedirectResponse;
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
        if ($listen_eintragung->user_id != auth()->id()) {
            $listen_eintragung->updateOrFail([
                'user_id' => null,
            ]);
        }

        if ($listen_eintragung->created_by == auth()->id()) {
            $listen_eintragung->delete();
        }

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Eintrag wurde gelöscht',
        ]);
    }
}
