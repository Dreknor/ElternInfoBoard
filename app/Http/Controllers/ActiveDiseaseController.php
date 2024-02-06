<?php

namespace App\Http\Controllers;

use App\Http\Requests\createActiveDiseaseRequest;
use App\Model\ActiveDisease;
use App\Model\Disease;
use Illuminate\Http\Request;

class ActiveDiseaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage diseases');
    }

    public function extend(ActiveDisease $disease)
    {
        $disease->update(['end' => $disease->end->addDays($disease->disease->aushang_dauer)]);
        return redirect()->back()->with([
            'Meldung' => 'Krankmeldung wurde erfolgreich verlängert',
            'type' => 'success',
        ]);
    }
    public function activate(ActiveDisease $disease)
    {
        $existingActiveDisease = ActiveDisease::query()
            ->where('disease_id', $disease->disease_id)
            ->whereDate('start', '<=', now())
            ->whereDate('end', '>=', now())->first();

        if ($existingActiveDisease) {

            $existingActiveDisease->update(['end' => $existingActiveDisease->end->addDays($disease->disease->aushang_dauer)]);
            $disease->delete();

            return redirect()->back()->with([
                'Meldung' => 'Es existiert bereits eine aktive Krankmeldung für diese Krankheit, daher wurde diese verlängert',
                'type' => 'danger',
            ]);
        } else {
            $disease->update(['active' => true]);
            return redirect()->back()->with([
                'Meldung' => 'Krankmeldung wurde erfolgreich aktiviert',
                'type' => 'success',
            ]);
        }

    }

    public function create()
    {
        return view('krankmeldung.createDisease', [
            'diseases' => Disease::all('id', 'name'),
            'activeDiseases' => ActiveDisease::whereDate('start', '<=', now())->whereDate('end', '>=', now())->get(),
        ]);
    }

    public function store(createActiveDiseaseRequest $request)
    {
        $disease = Disease::find($request->disease_id);

        $activeDisease = ActiveDisease::where('user_id', auth()->id())->where('disease_id', $request->disease_id)->whereDate('start', '<=', now())->whereDate('end', '>=', now())->first();

        if ($activeDisease) {
            $activeDisease->update(['end' => $activeDisease->end->addDays($disease->aushang_dauer)]);

            return redirect(url('/'))->with([
                'Meldung' => 'Krankmeldung wurde erfolgreich verlängert',
                'type' => 'success',
            ]);


        } else {
            ActiveDisease::insert([
                'user_id' => auth()->id(),
                'disease_id' => $request->disease_id,
                'start' => now(),
                'end' => now()->addDays($disease->aushang_dauer),
                'active' => false,
            ]);

            return redirect(url('/'))->with([
                'Meldung' => 'Krankmeldung wurde erfolgreich eingetragen',
                'type' => 'success',
            ]);
        }


    }

    public function destroy(ActiveDisease $disease)
    {
        if (!auth()->user()->can('manage diseases')) {
            return redirect()->back()->with([
                'Meldung' => 'Du hast keine Berechtigung diese Krankmeldung zu löschen',
                'type' => 'danger',
            ]);
        }

        if ($disease->active) {
            return redirect()->back()->with([
                'Meldung' => 'Du kannst keine aktive Krankmeldung löschen',
                'type' => 'danger',
            ]);
        }

        $disease->delete();
        return redirect()->back();
    }

    public function update(ActiveDisease $disease)
    {
        if (!auth()->user()->can('manage diseases')) {
            return redirect()->back()->with([
                'Meldung' => 'Du hast keine Berechtigung diese Krankmeldung zu löschen',
                'type' => 'danger',
            ]);
        }

        $disease->update(['active' => false]);
        return redirect()->back();
    }
}
