<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DatenschutzController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return view('datenschutz.show', [
            'user' => $user,
        ]);
    }
}
