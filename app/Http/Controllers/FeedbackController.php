<?php

namespace App\Http\Controllers;

use App\Http\Requests\KontaktRequest;
use App\Mail\dailyMailReport;
use App\Mail\SendFeedback;
use App\Model\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Mail;
use App\Model\Mail as MailModel;

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
            'emails' => (!auth()->user()->can('see mails')) ? auth()->user()->mails : MailModel::orderByDesc('created_at')->paginate(30)
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
                        'Meldung' => $error,
                    ]);
                }


            }
        }
        $mail = new MailModel([
            'senders_id' => auth()->id(),
            'to' => $email,
            'subject' => $request->betreff,
            'text' => $request->text
        ]);
        $mail->save();
        $mail->addAllMediaFromRequest(['files'])
            ->each(function ($fileAdder) {
                $fileAdder->toMediaCollection('files');
            });
        foreach ($mail->getMedia() as $media) {
            $data['document'][] = $media->getPath();
        }
        try {
            Mail::to($email)->cc($request->user()->email)->send(new SendFeedback($request->text, $request->betreff, $data));
            $feedback = [
                'type' => 'success',
                'Meldung' => 'Nachricht wurde versandt',
            ];
        } catch (Exception $e) {
            $feedback = [
                'type' => 'danger',
                'Meldung' => 'Fehler beim Versand der Nachricht. Fehler: ' . $e->getMessage(),
            ];

        }

        return redirect()->back()->with($feedback);
    }

    public function showMail(MailModel $mail)
    {
        if (!auth()->user()->can('see mails') and auth()->id() != $mail->senders_id) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Zugriff verweigert' . auth()->id() . ' != ' . $mail->senders_id
            ]);
        }

        return view('feedback.showMail', [
            'mail' => $mail
        ]);
    }

    public function dailyReport()
    {
        $mails = MailModel::where('created_at', '<', Carbon::now())
            ->where('created_at', '>=', Carbon::yesterday()->startOfDay())
            ->get();
        Mail::to(config('mail.from.address'))
            ->queue(new dailyMailReport($mails));
    }
}
