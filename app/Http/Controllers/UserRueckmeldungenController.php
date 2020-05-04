<?php

namespace App\Http\Controllers;

use App\Jobs\SendRueckmeldung;
use App\Mail\UserRueckmeldung AS UserMail;
use App\Mail\UserRueckmeldung;
use App\Model\Post;
use App\Model\UserRueckmeldungen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UserRueckmeldungenController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','password_expired']);
    }

    public function sendRueckmeldung(Request $request, $post_id)
    {
        $user = auth()->user();

        $post_id = Post::find($post_id);

        $rueckmeldungUser = UserRueckmeldungen::firstOrNew([
            "post_id" => $post_id->id,
            "users_id" => $user->id
        ]);

        $rueckmeldungUser->text = $request->input('text');
        $rueckmeldungUser->save();

        $Empfaenger = $post_id->rueckmeldung->empfaenger;

        $Rueckmeldung = [
            "text"  => $request->input('text').'<br>'.auth()->user()->name,
            "subject"   => "Rückmeldung $post_id->header",
            'name'  => $user->name,
            "email" => $user->email,
            'empfaenger'    =>  $Empfaenger
        ];

        if ($user->sendCopy == 1){
            Mail::to($Empfaenger)
                ->cc($user->email)
                ->send(new UserRueckmeldung($Rueckmeldung));
        } else {
            Mail::to($Empfaenger)
                ->send(new UserRueckmeldung($Rueckmeldung));
        }



        return redirect(url('/home#'.$post_id->id))->with([
                "id" => $post_id->id,
                "type"  => "success",
                "Meldung"   => "Die Rückmeldung wurde der Schule gesendet"
            ]);






    }

    public function edit(UserRueckmeldungen $userRueckmeldungen){

        return view('userrueckmeldung.edit',[
           "Rueckmeldung" => $userRueckmeldungen
        ]);
    }

    public function update(Request $request, UserRueckmeldungen $userRueckmeldungen){

        $user = auth()->user();

        if ($userRueckmeldungen->users_id != $user->id and $userRueckmeldungen->users_id != $user->sorg2){
            return redirect()->back()->with([
               "type"   => "warning",
               'Meldung'    => "Fehlende Berechtigung"
            ]);
        }

        $userRueckmeldungen->update([
           "text"   => $request->input('text'),
           "users_id"   => $user->id
        ]);


        $Empfaenger = $userRueckmeldungen->nachricht->rueckmeldung->empfaenger;

        $Rueckmeldung = [
            "text"  => $request->input('text').'<br>'.auth()->user()->name,
            "subject"   => "geänderte Rückmeldung ".$userRueckmeldungen->nachricht->header,
            'name'  => $user->name,
            "email" => $user->email,
            'empfaenger'    =>  $Empfaenger
        ];

        if ($user->sendCopy == 1){
            Mail::to($Empfaenger)
                ->cc($user->email)
                ->send(new UserRueckmeldung($Rueckmeldung));
        } else {
            Mail::to($Empfaenger)
                ->send(new UserRueckmeldung($Rueckmeldung));
        }

        return redirect(url('/home#'.$userRueckmeldungen->post_id))->with([
            'type'  => "success",
            "Meldung"   => "Rückmeldung versendet",
            "RueckmeldungCheck" => $userRueckmeldungen->post_id
        ]);
    }
}
