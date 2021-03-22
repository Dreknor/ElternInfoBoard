<?php

namespace App\Http\Controllers;

use App\Http\Requests\KontaktRequest;
use App\Mail\SendFeedback;
use App\Model\User;
use Illuminate\Support\Facades\Mail;

class FeedbackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show()
    {
        return view('feedback.show', [
            'mitarbeiter'   => User::whereHas('roles', function ($q) {
                $q->where('name', 'Mitarbeiter');
            })->orderBy('name')->get(),

        ]);
    }

    public function send(KontaktRequest $request)
    {
        if ($request->mitarbeiter != '') {
            $email = User::query()->where('id', $request->mitarbeiter)->value('email');
        } else {
            $email = config('mail.from.address');
        }

        $data = [];

        if ($request->hasFile('files')) {
            $files = $request->files->all();
            foreach ($files['files'] as $document) {
                // Check if uploaded file size was greater than
                // maximum allowed file size
                if ($document->getError() == 1) {
                    $max_size = $document->getMaxFileSize() / 1024 / 1024;  // Get size in Mb
                    $error = 'The document size must be less than '.$max_size.'Mb.';

                    return redirect()->back()->with([
                        'type' => 'danger',
                        'Meldung'=>$error,
                    ]);
                }

                $data[] = [
                    'document' => $document,
                ];
            }
        }

        Mail::to($email)->send(new SendFeedback($request->text, $data));

        return redirect()->back()->with([
           'type'   => 'success',
           'Meldung'    => 'Feedback wurde versandt',
        ]);
    }
}
