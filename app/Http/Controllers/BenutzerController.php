<?php

namespace App\Http\Controllers;

use App\Http\Requests\editUserRequest;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BenutzerController extends Controller
{

    protected $user;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {

            $this->user = Auth::user();

            return $next($request);
        });
    }

    public function show(){

        return view('user.settings', [
            "user"  => $this->user
        ]);
    }

    public function update(editUserRequest $request){

       $user = User::find($this->user->id);
       $user->update($request->all());

        return redirect()->back()->with([
           'type'   => "success",
           "Meldung"    => "Gespeichert."
        ]);

    }
}
