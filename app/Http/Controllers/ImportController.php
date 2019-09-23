<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:import user']);
    }

    public function importForm(){

        return view('user.import');
    }

    public function import(Request $request){


        $header = [
            "klassenstufe" => $request->input("klassenstufe")-1,
            "lerngruppe" => $request->input("lerngruppe")-1,
            "S1Vorname" => $request->input("S1Vorname")-1,
            "S1Nachname" => $request->input("S1Nachname")-1,
            "S1Email" => $request->input("S1Email")-1,
            "S2Email" => $request->input("S2Email")-1,
            "S2Vorname" => $request->input("S2Vorname")-1,
            "S2Nachname" => $request->input("S2Nachname")-1,
        ];


        Excel::import(new UsersImport($header), request()->file('file'));

        return redirect(url('users'));
    }
}
