<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateChildRequest;
use App\Model\Child;
use App\Model\ChildCheckIn;

class ChildController extends Controller
{
    public function __construct()
    {

    }

    public function store(CreateChildRequest $request)
    {
        $this->middleware('auth');

        auth()->user()->children()->create($request->validated());

        return redirect()->back()->with([
            'Meldung' => 'Kind wurde erfolgreich erstellt',
            'type' => 'success',
        ]);
    }

}
