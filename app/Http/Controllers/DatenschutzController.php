<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DatenschutzController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(){

        $user = auth()->user();

        return view('datenschutz.show',[
            'user' => $user
        ]);
    }
}
