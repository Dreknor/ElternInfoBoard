<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VertretungsplanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view vertretungsplan']);
    }

    public function index (){
        $gruppen = "";

        if (!auth()->user()->can('view vertretungsplan all')){
            foreach (auth()->user()->groups as $group){
                $gruppen.="/".$group->name;
            }

        }

        return view('vertretungsplan.index', [
            'gruppen'=>$gruppen
        ]);
    }
}
