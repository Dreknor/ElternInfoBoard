<?php

namespace App\Http\Controllers;

use App\Mail\SendFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FeedbackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(){
        return view('feedback.show');
    }

    public function send(Request $request){

        Mail::to('daniel.roehrich@esz-radebeul.de')->send(new SendFeedback($request->text));

        return redirect()->back()->with([
           "type"   => "success",
           "Meldung"    => "Feedback wurde versandt"
        ]);
    }
}
