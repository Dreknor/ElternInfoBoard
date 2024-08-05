<?php

namespace App\Http\Controllers;

use App\Mail\UserRueckmeldung as UserRueckmeldungMail;
use App\Model\AbfrageAntworten;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\UserRueckmeldungen;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Mail;

class UserRueckmeldungenController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        $this->middleware(['auth', 'password_expired']);
    }

    /**
     * @param Request $request
     * @param Rueckmeldungen $rueckmeldung
     * @return RedirectResponse
     */
    public function store(Request $request, Rueckmeldungen $rueckmeldung)
    {
        if (auth()->user()->rueckmeldung?->where('rueckmeldung_id', $rueckmeldung->post->id)->count() > 0 and $rueckmeldung->multiple != 1) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Abfrage wurde bereits beantwortet',
            ]);
        }

        $userRueckmeldung = new UserRueckmeldungen([
            'post_id' => $rueckmeldung->post->id,
            'users_id' => auth()->id(),
            'rueckmeldung_number' => auth()->user()->rueckmeldung?->where('rueckmeldung_id', $rueckmeldung->post->id)->count() + 1,
            'text' => '',
        ]);
        $userRueckmeldung->save();

        $this->generateAnswerModels($request, $userRueckmeldung);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Abfrage wurde gespeichert',
        ]);
    }

    /**
     * @param Request $request
     * @param $post_id
     * @return Application|RedirectResponse|Redirector
     */
    public function sendRueckmeldung(Request $request, $post_id)
    {
        $user = $request->user();
        $post_id = Post::find($post_id);
        $rueckmeldung = $post_id->rueckmeldung;

        if (auth()->user()->rueckmeldung?->where('rueckmeldung_id', $rueckmeldung->post->id)->count() > 0 and $rueckmeldung->multiple != 1) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Abfrage wurde bereits beantwortet',
            ]);
        }
        $rueckmeldungUser = UserRueckmeldungen::create([
            'post_id' => $post_id->id,
            'users_id' => $user->id,
            'rueckmeldung_number' => auth()->user()->rueckmeldung?->where('post_id', $rueckmeldung->post->id)->count() + 1,
            'text' => $request->input('text'),
        ]);

        $rueckmeldungUser->save();

        $Empfaenger = $post_id->rueckmeldung->empfaenger;

        $Rueckmeldung = [
            'text' => $request->input('text').'<br>'.$request->user()->name,
            'subject' => "Rückmeldung $post_id->header",
            'name' => $user->name,
            'email' => $user->email,
            'empfaenger' => $Empfaenger,
        ];

        if ($user->sendCopy == 1) {
            Mail::to($Empfaenger)
                ->cc($user->email)
                ->queue(new UserRueckmeldungMail($Rueckmeldung));
        } else {
            Mail::to($Empfaenger)
                ->queue(new UserRueckmeldungMail($Rueckmeldung));
        }

        return redirect(url('/home#'.$post_id->id))->with([
            'id' => $post_id->id,
            'type' => 'success',
            'Meldung' => 'Die Rückmeldung wurde der Schule gesendet',
        ]);
    }

    /**
     * @param UserRueckmeldungen $userRueckmeldungen
     * @return Application|Factory|View|RedirectResponse
     */
    public function edit(UserRueckmeldungen $userRueckmeldungen)
    {
        if ($userRueckmeldungen->users_id != auth()->id() and $userRueckmeldungen->users_id != auth()->user()->sorg2) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        switch ($userRueckmeldungen->nachricht->rueckmeldung->type) {
            case 'email':
                return view('userrueckmeldung.edit', [
                    'Rueckmeldung' => $userRueckmeldungen,
                ]);
                break;
            case 'abfrage':
                $rueckmeldung = $userRueckmeldungen->nachricht->rueckmeldung;
                $userRueckmeldungen->load('answers');

                return view('userrueckmeldung.editAbfrage', [
                    'userRueckmeldung' => $userRueckmeldungen,
                    'rueckmeldung' => $rueckmeldung,
                ]);
                break;
            default:
                return redirect()->back()->with([
                    'type' => 'warning',
                    'Meldung' => 'Bearbeiten ist nicht möglich',
                ]);
                break;
        }
    }

    /**
     * @param Request $request
     * @param UserRueckmeldungen $userRueckmeldungen
     * @return Application|RedirectResponse|Redirector
     */
    public function update(Request $request, UserRueckmeldungen $userRueckmeldungen)
    {
        $user = $request->user();

        if ($userRueckmeldungen->users_id != $user->id and $userRueckmeldungen->users_id != $user->sorg2) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Fehlende Berechtigung',
            ]);
        }
        $rueckmeldung = $userRueckmeldungen->nachricht->rueckmeldung;

        switch ($rueckmeldung->type) {
            case 'email':
                $userRueckmeldungen->update([
                    'text' => $request->input('text'),
                    'users_id' => $user->id,
                ]);

                $Empfaenger = $userRueckmeldungen->nachricht->rueckmeldung->empfaenger;

                $Rueckmeldung = [
                    'text' => $request->input('text').'<br>'.$request->user()->name,
                    'subject' => 'geänderte Rückmeldung '.$userRueckmeldungen->nachricht->header,
                    'name' => $user->name,
                    'email' => $user->email,
                    'empfaenger' => $Empfaenger,
                ];

                if ($user->sendCopy == 1) {
                    Mail::to($Empfaenger)
                        ->cc($user->email)
                        ->queue(new UserRueckmeldungMail($Rueckmeldung));
                } else {
                    Mail::to($Empfaenger)
                        ->queue(new UserRueckmeldungMail($Rueckmeldung));
                }
                break;
            case 'abfrage':
                AbfrageAntworten::where('rueckmeldung_id', $userRueckmeldungen->id)->delete();

                $this->generateAnswerModels($request, $userRueckmeldungen);
                break;

            default:
                return redirect()->back()->with([
                    'type' => 'warning',
                    'Meldung' => 'Änderung nicht möglich',
                ]);
                break;
        }

        return redirect(url('/home#'.$userRueckmeldungen->post_id))->with([
            'type' => 'success',
            'Meldung' => 'Rückmeldung versendet',
            'RueckmeldungCheck' => $userRueckmeldungen->post_id,
        ]);
    }

    /**
     * @param Request $request
     * @param UserRueckmeldungen $userRueckmeldung
     * @return void
     */
    public function generateAnswerModels(Request $request, UserRueckmeldungen $userRueckmeldung): void
    {
        $answers = [];

        if (array_key_exists('options', $request->answers)) {
            foreach ($request->answers['options'] as $option) {
                $answers[] = [
                    'rueckmeldung_id' => $userRueckmeldung->id,
                    'user_id' => auth()->id(),
                    'option_id' => $option,
                    'answer' => '1',
                ];
            }
        }
        if (array_key_exists('text', $request->answers)) {
            foreach ($request->answers['text'] as $key => $answer) {
                $answers[] = [
                    'rueckmeldung_id' => $userRueckmeldung->id,
                    'user_id' => auth()->id(),
                    'option_id' => $key,
                    'answer' => $answer,
                ];
            }
        }

        AbfrageAntworten::insert($answers);
    }
}
