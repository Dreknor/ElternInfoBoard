<?php

namespace App\Http\Controllers;

use App\Http\Requests\KontaktRequest;
use App\Mail\dailyMailReport;
use App\Mail\SendFeedback;
use App\Model\Mail as MailModel;
use App\Model\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;


class FeedbackController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return View
     */
    public function show()
    {

        if (auth()->user()->can('see mails')) {
            $mails = MailModel::withoutGlobalScope('own')
                ->orderBy('created_at', 'desc')
                ->paginate(25);
        } else {



            $mails = MailModel::where('senders_id', auth()->id())
                ->orWhere('to', auth()->user()->email)
                ->orderBy('created_at', 'desc')->paginate(25);
        }

        return view('feedback.show', [
            'mitarbeiter' => User::whereHas('roles', function ($q) {
                $q->where('name', 'Mitarbeiter');
            })->orWhereHas('permissions', function ($q) {
                $q->where('name', 'show in contact form');
            })->orderBy('name')->get(),
            'mails' => $mails,
        ]);
    }

    /**
     *
     * Send Mail
     * @param KontaktRequest $request
     * @return RedirectResponse
     */
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
                    $error = 'The document size must be less than ' . $max_size . 'Mb.';

                    return redirect()->back()->with([
                        'type' => 'danger',
                        'Meldung' => $error,
                    ]);
                }
                $data[] = $document;
            }
        }


        //create Mail Model for logging Mail in Database
        $mail = new MailModel([
            'senders_id' => auth()->id(),
            'to' => $email,
            'subject' => $request->betreff,
            'text' => $request->text,
        ]);
        $mail->save();
        $mail->addAllMediaFromRequest(['files'])
            ->each(function ($fileAdder) {
                $fileAdder->toMediaCollection('files');
            });
        foreach ($mail->getMedia('files') as $media) {
            $data['document'][] = $media;
        }

        Mail::to($email)->cc($request->user()->email)->send(new SendFeedback($request->text, $request->betreff, $data));
        $feedback = [
            'type' => 'success',
            'Meldung' => 'Nachricht wurde versandt',
        ];

        try {

        } catch (Exception $e) {

            $feedback = [
                'type' => 'danger',
                'Meldung' => 'Fehler beim Versand der Nachricht. Fehler: ' . $e->getMessage(),
            ];
        }

        return redirect()->back()->with($feedback);
    }

    /**
     * @param MailModel $mail
     * @return View|RedirectResponse
     */
    public function showMail(MailModel $mail)
    {
        if (! auth()->user()->can('see mails') and auth()->id() != $mail->senders_id) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Zugriff verweigert'.auth()->id().' != '.$mail->senders_id,
            ]);
        }

        return view('feedback.showMail', [
            'mail' => $mail,
        ]);
    }

    /**
     * Called from Console/Kernel
     * @return void
     */
    public function dailyReport()
    {
        $mails = MailModel::where('created_at', '<', Carbon::now())
            ->where('created_at', '>=', Carbon::yesterday()->startOfDay())
            ->get();
        Mail::to(config('mail.from.address'))
            ->queue(new dailyMailReport($mails));
    }
}
