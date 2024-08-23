<?php

namespace App\Http\Controllers;

use App\Exports\AbfrageExport;
use App\Http\Requests\createAbfrageRequest;
use App\Http\Requests\createRueckmeldungRequest;
use App\Http\Requests\updateRueckmeldeDateRequest;
use App\Mail\ErinnerungRuecklaufFehlt;
use App\Model\AbfrageAntworten;
use App\Model\AbfrageOptions;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\UserRueckmeldungen;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

use PDF;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RueckmeldungenController extends Controller
{
    /**
     * @param updateRueckmeldeDateRequest $request
     * @param Rueckmeldungen $rueckmeldung
     * @return RedirectResponse
     */
    public function updateDate(updateRueckmeldeDateRequest $request, Rueckmeldungen $rueckmeldung)
    {
        $rueckmeldung->update([
            'ende' => $request->date,
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Rückmeldefrist wurde verlängert',
        ]);
    }

    /**
     * @param Post $post
     * @param $type
     * @return View|RedirectResponse
     */
    public function create(Post $post, $type)
    {
        if ($type != 'abfrage'){
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Rückmeldung erstellen fehlgeschlagen. Rückmeldetyp nicht gefunden.'
            ]);
        }

        return view('nachrichten.createAbfrage', [
            'nachricht' => $post,
        ])->with([
            'type' => 'success',
            'Meldung' => 'Nachricht wurde erstellt',
        ]);
    }

    /**
     * @param Rueckmeldungen $rueckmeldung
     * @return View|RedirectResponse
     */
    public function editAbfrage(Rueckmeldungen $rueckmeldung)
    {
        if ($rueckmeldung->userRueckmeldungen()->count() > 0) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Es wurden bereits Rückmeldungen gegeben',
            ]);
        }

        return view('nachrichten.editAbfrage', [
            'rueckmeldung' => $rueckmeldung,
        ]);
    }

    /**
     * @param createAbfrageRequest $request
     * @param Rueckmeldungen $rueckmeldung
     * @return RedirectResponse
     */
    public function updateAbfrage(createAbfrageRequest $request, Rueckmeldungen $rueckmeldung)
    {
        if ($rueckmeldung->userRueckmeldungen->count() > 0) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Es wurden bereits Rückmeldungen gegeben',
            ]);
        }

        $rueckmeldung->update($request->validated());

        $rueckmeldung->update([
            'text' => $request->description,
            'max_answers' => $request->max_number,
        ]);

        AbfrageOptions::where('rueckmeldung_id', $rueckmeldung->id)->delete();

        $options = [];
        foreach ($request->options as $key => $value) {
            if ($value != "") {
                $options[] = [
                    'rueckmeldung_id' => $rueckmeldung->id,
                    'type' => $request->types[$key],
                    'option' => $value,
                    'required' => $request->required[$key],
                ];
            }

        }

        AbfrageOptions::insert($options);

        return redirect(url('/home#'.$rueckmeldung->post->id))->with([
            'type' => 'success',
            'Meldung' => 'Abfrage wurde geändert.',
        ]);
    }

    /**
     * Show all Rueckmeldungen
     */
    public function index()
    {
        if (! auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        return view('rueckmeldungen.index', [
            'rueckmeldungen' => Rueckmeldungen::whereHas('post')->with('post')->withCount('userRueckmeldungen as rueckmeldungen')->orderByDesc('ende')->get(),
        ]);
    }

    //zeigt alle Rückmeldungen zu einem Post

    /**
     * @param Rueckmeldungen $rueckmeldung
     * @return View|RedirectResponse
     */
    public function show(Rueckmeldungen $rueckmeldung)
    {
        if (!auth()->user()->can('manage rueckmeldungen') and $rueckmeldung->post->author_id != auth()->id()) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        if ($rueckmeldung->type == 'email') {
            return view('rueckmeldungen.show', [
                'rueckmeldungen' => $rueckmeldung->userRueckmeldungen()->orderByDesc('created_at')->get(),
                'rueckmeldung' => $rueckmeldung,
            ]);
        } elseif ($rueckmeldung->type == 'abfrage') {
            return view('rueckmeldungen.showAbfrage', [
                'rueckmeldung' => $rueckmeldung->load('userRueckmeldungen'),
            ]);
        } else {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Keine Darstellung für diesen Rückmeldetyp möglich',
            ]);
        }


    }

    /**
     * @param Rueckmeldungen $rueckmeldung
     * @param $user_id
     * @return RedirectResponse
     */
    public function download(Rueckmeldungen $rueckmeldung, $user_id)
    {
        if (!auth()->user()->can('manage rueckmeldungen') and $rueckmeldung->post->author_id != auth()->id()) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $pdf = PDF::loadView('pdf.userRueckmeldungen', [
            'nachricht' => $rueckmeldung->post,
            'rueckmeldungen' => $rueckmeldung->userRueckmeldungen()->where('users_id', $user_id)->get(),
        ]);

        return $pdf->download(Carbon::now()->format('Y-m-d').'_Rückmeldung.pdf');
    }

    /**
     * @param Rueckmeldungen $rueckmeldung
     * @return RedirectResponse|BinaryFileResponse
     */
    public function downloadAll(Rueckmeldungen $rueckmeldung)
    {
        if (!auth()->user()->can('manage rueckmeldungen') and $rueckmeldung->post->author != auth()->id()) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt für den Download der Rückmeldungen ',
            ]);
        }

        switch ($rueckmeldung->type) {
            case 'email':
                //PDF für Text-Rückmeldungen
                $pdf = PDF::loadView('pdf.userRueckmeldungen', [
                    'nachricht' => $rueckmeldung->post,
                    'rueckmeldungen' => $rueckmeldung->userRueckmeldungen,
                ]);
                return $pdf->download(Carbon::now()->format('Y-m-d').'_Nachrichten.pdf');
                break;
            case 'abfrage':
                //Excel für Abfrage-Rückmeldungen
                return Excel::download(new AbfrageExport($rueckmeldung->options, $rueckmeldung->userRueckmeldungen), 'Rueckmeldung_' . Carbon::now()->format('Ymd_Hi') . '.xlsx');
                break;
        }

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Fehlerhafter Rückmeldetyp'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param createRueckmeldungRequest $request
     * @param $posts_id
     * @return RedirectResponse
     */
    public function store(createRueckmeldungRequest $request, $posts_id)
    {
        if (!auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }
        $rueckmeldung = new Rueckmeldungen($request->validated());
        $rueckmeldung->post_id = $posts_id;
        $rueckmeldung->save();

        return redirect()->to(url('/home'))->with([
            'type' => 'success',
            'Meldung' => 'Rückmeldung erstellt.',
        ]);
    }


    public function editUserAbfrage(Rueckmeldungen $rueckmeldung, UserRueckmeldungen $userrueckmeldung)
    {
        if (!auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }
        return view('rueckmeldungen.updateAbfrage', [
            'rueckmeldung' => $rueckmeldung,
            'userRueckmeldung' => $userrueckmeldung
        ]);
    }

    public function updateUserAbfrage(Request $request, Rueckmeldungen $rueckmeldung, UserRueckmeldungen $userrueckmeldung)
    {
        if (!auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }
        AbfrageAntworten::where('rueckmeldung_id', $userrueckmeldung->id)->delete();

        $userrueckmeldungenController = new UserRueckmeldungenController();
        $userrueckmeldungenController->generateAnswerModels($request, $userrueckmeldung);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => "Daten geändert"
        ]);
    }

    public function createUserAbfrage(Rueckmeldungen $rueckmeldung)
    {
        if (!auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $users = $rueckmeldung->post->users;
        return view('rueckmeldungen.createUserRueckmeldungAbfrage', [
            'rueckmeldung' => $rueckmeldung,
            'users' => $users->sortBy('name')->unique('id')
        ]);
    }

    public function storeNewUserAbfrage(Request $request, Rueckmeldungen $rueckmeldung)
    {
        if (!auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $userRueckmeldung = UserRueckmeldungen::where('post_id', $rueckmeldung->post_id)->where('users_id', $request->user)->get();

        if ($rueckmeldung->multiple != 1 and $userRueckmeldung->count() > 0) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Rückmeldung bereits abgegeben',
            ]);
        }

        $newUserRueckmeldung = new UserRueckmeldungen([
            'post_id' => $rueckmeldung->post_id,
            'users_id' => $request->user,
            'text' => ' ',
            'rueckmeldung_number' => ($userRueckmeldung->first() != null) ? $userRueckmeldung->first()->rueckmeldung_number : 1
        ]);
        $newUserRueckmeldung->save();

        $userrueckmeldungenController = new UserRueckmeldungenController();

        $userrueckmeldungenController->generateAnswerModels($request, $newUserRueckmeldung);

        return redirect(url('rueckmeldungen/' . $rueckmeldung->id . '/show'))->with([
            'type' => 'success',
            'Meldung' => 'Rückmeldung gespeichert',
        ]);

    }

    public function deleteUserAbfrage(Rueckmeldungen $rueckmeldung, UserRueckmeldungen $userrueckmeldung)
    {
        if (!auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }
        AbfrageAntworten::where('rueckmeldung_id', $userrueckmeldung->id)->delete();
        $userrueckmeldung->delete();


        return redirect(url('rueckmeldungen/' . $rueckmeldung->id . '/show'))->with([
            'type' => 'success',
            'Meldung' => "Rückmeldung gelöscht"
        ]);
    }
    /**
     * Store a newly created Abfrage in storage.
     *
     * @param createAbfrageRequest $request
     * @param $posts_id
     * @return RedirectResponse
     */
    public function storeAbfrage(createAbfrageRequest $request, $posts_id)
    {
        $rueckmeldung = new Rueckmeldungen($request->validated());
        $rueckmeldung->type = 'abfrage';
        $rueckmeldung->text = $request->description;
        $rueckmeldung->post_id = $posts_id;
        $rueckmeldung->max_answers = ($request->max_number > 0) ? $request->max_number : '0';
        $rueckmeldung->save();

        $options = [];
        foreach ($request->options as $key => $value) {
            if ($value != "") {
                $options[] = [
                    'rueckmeldung_id' => $rueckmeldung->id,
                    'type' => $request->types[$key],
                    'required' => $request->required[$key],
                    'option' => $value,
                ];
            }

        }

        AbfrageOptions::insert($options);

        return redirect()->to(url('/home'))->with([
            'type' => 'success',
            'Meldung' => 'Rückmeldung erstellt.',
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Rueckmeldungen  $rueckmeldungen
     * @return RedirectResponse
     */
    public function update(Request $request, $post_id)
    {
        $rueckmeldung = Rueckmeldungen::firstOrNew([
            'post_id' => $post_id,
        ]);

        $rueckmeldung->fill($request->all());
        $rueckmeldung->save();

        $post = Post::find($post_id);

        if ($rueckmeldung->ende->greaterThan($post->archiv_ab)) {
            $post->update([
                'archiv_ab' => $rueckmeldung->ende,
            ]);
        }

        return redirect()->to(url('home'))->with([
            'type' => 'success',
            'Meldung' => 'Rückmeldung gespeichert',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Rueckmeldungen  $rueckmeldungen
     * @return JsonResponse
     */
    public function destroy(Rueckmeldungen $rueckmeldung)
    {
        $rueckmeldung->delete();

        return response()->json([
            'message' => 'Gelöscht',
        ]);
    }

    /**
     * @param Post $post
     * @return RedirectResponse
     */
    public function destroyAbfrage(Post $post)
    {
        $rueckmeldung = $post->rueckmeldung;

        if (!$rueckmeldung->type == 'abfrage') {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Kann nicht gelöscht werden'
            ]);
        }

        $rueckmeldung->options()->delete();
        $rueckmeldung->delete();

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Abfrage wurde gelöscht'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Rueckmeldungen $rueckmeldungen
     * @return RedirectResponse
     */
    public function destroyRueckmeldung(Rueckmeldungen $rueckmeldungen)
    {
        $rueckmeldungen->delete();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Rückmeldung gelöscht',
        ]);
    }

    /**
     * @return void
     */
    public function sendErinnerung()
    {
        $rueckmeldungen = Rueckmeldungen::whereBetween('ende', [Carbon::now(), Carbon::now()->addDays(3)])->where('pflicht', 1)->with(['post', 'post.users', 'post.users.userRueckmeldung',  'post.users.sorgeberechtigter2'])->get();
        foreach ($rueckmeldungen as $Rueckmeldung) {
            if ($Rueckmeldung->post->released == 1) {
                $user = $Rueckmeldung->post->users;
                $user = $user->unique('id');

                foreach ($user as $User) {
                    $RueckmeldungUser = $User->getRueckmeldung()->where('post_id', $Rueckmeldung->post->id)->first();
                    if (is_null($RueckmeldungUser)) {
                        $email = $User->email;
                        Mail::to($email)->send(new ErinnerungRuecklaufFehlt($User->email, $User->name, $Rueckmeldung->post->header, $Rueckmeldung->ende->endOfDay(), $Rueckmeldung->post->id));
                    }
                }
            }
        }
    }

    /**
     * @param Rueckmeldungen $rueckmeldungen
     * @return RedirectResponse
     */
    public function updateCommentable(Rueckmeldungen $rueckmeldungen)
    {
        if ($rueckmeldungen->commentable) {
            $rueckmeldungen->update([
                'commentable' => false,
            ]);
        } else {
            $rueckmeldungen->update([
                'commentable' => true,
            ]);
        }

        return redirect()->back();
    }

    /**
     * @param Request $request
     * @param $posts_id
     * @return RedirectResponse
     */
    public function createImageRueckmeldung(Request $request, $posts_id)
    {
        $posts = Post::find($posts_id);
        $rueckmeldung = new Rueckmeldungen([
            'post_id' => $posts_id,
            'type' => 'bild',
            'commentable' => 1,
            'empfaenger' => $request->user()->email,
            'ende' => $posts->archiv_ab,
            'text' => ' ',
        ]);
        $rueckmeldung->save();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Bild-Upload mit Kommentaren erstellt.',
        ]);
    }

    /**
     * @param Request $request
     * @param $posts_id
     * @return RedirectResponse
     */
    public function createDiskussionRueckmeldung(Request $request, $posts_id)
    {
        $posts = Post::find($posts_id);
        $rueckmeldung = new Rueckmeldungen([
            'post_id' => $posts_id,
            'type' => 'commentable',
            'commentable' => 1,
            'empfaenger' => $request->user()->email,
            'ende' => $posts->archiv_ab,
            'text' => ' ',
        ]);
        $rueckmeldung->save();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Diskussion erstellt.',
        ]);
    }
}
