<?php

namespace App\Http\Controllers;

use App\Http\Requests\KontaktRequest;
use App\Mail\SendFeedback;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class FeedbackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(){
        return view('feedback.show',[
            "mitarbeiter"   => User::whereHas("roles", function($q){ $q->where("name", "Mitarbeiter"); })->orderBy('name')->get()

        ]);
    }

    public function send(KontaktRequest $request){

        if ($request->mitarbeiter != ""){
            $email = User::query()->where('id', $request->mitarbeiter)->value('email');
        } else {
            $email = "info@esz-radebeul.de";
        }



        Mail::to($email)->bcc('daniel.roehrich@esz-radebeul.de')->send(new SendFeedback($request->text));

        return redirect()->back()->with([
           "type"   => "success",
           "Meldung"    => "Feedback wurde versandt"
        ]);
    }
}
