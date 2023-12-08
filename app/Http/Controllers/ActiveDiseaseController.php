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

    public function activate(ActiveDisease $disease)
    {
        $disease->update(['active' => true]);
        return redirect()->back();
    }

    public function create()
    {
        return view('krankmeldung.createDisease', [
            'diseases' => Disease::all('id', 'name'),
        ]);
    }

    public function store(createActiveDiseaseRequest $request)
    {
        $disease = Disease::find($request->disease_id);

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
