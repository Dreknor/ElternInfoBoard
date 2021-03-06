<?php

namespace App\Http\Controllers;

use App\Http\Requests\editUserRequest;
use App\Model\Changelog;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

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

    public function show(Request $request)
    {
        if ($request->session()->get('changelog') == true) {
            $changelog = Changelog::where('changeSettings', 1)->orderByDesc('created_at')->first();
        } else {
            $changelog = null;
        }

        return view('user.settings', [
            'user'  => $this->user,
            'changelog' => $changelog,
        ]);
    }

    public function update(editUserRequest $request)
    {
        $user = User::find($this->user->id);
        $user->update($request->all());

        return redirect()->back()->with([
           'type'   => 'success',
           'Meldung'    => 'Gespeichert.',
        ]);
    }
}
