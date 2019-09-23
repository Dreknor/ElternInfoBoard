<?php

namespace App\Http\Controllers;

use App\Mail\UserRueckmeldung;
use App\Model\Posts;
use App\Model\UserRueckmeldungen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UserRueckmeldungenController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','password_expired']);
    }

    public function sendRueckmeldung(Request $request, Posts $posts_id)
    {
        $user = auth()->user();

        $rueckmeldungUser = UserRueckmeldungen::firstOrNew([
            "posts_id" => $posts_id->id,
            "users_id" => $user->id
        ]);

        $rueckmeldungUser->text = $request->input('text');


        $rueckmeldungUser->save();
        Mail::to($posts_id->rueckmeldung->empfaenger)
           ->queue(new UserRueckmeldung($request->input('text'), "Rückmeldung $posts_id->header", $user));


        return redirect()->back()->with([
                "type"  => "success",
                "Meldung"    => "Rückmeldung gesendet"
            ]);






    }
}
