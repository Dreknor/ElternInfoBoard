<?php

namespace App\Http\Controllers;

use App\Exports\AbfrageExport;
use App\Http\Requests\createAbfrageRequest;
use App\Http\Requests\createRueckmeldungRequest;
use App\Http\Requests\updateRueckmeldeDateRequest;
use App\Mail\ErinnerungRuecklaufFehlt;
use App\Model\AbfrageOptions;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class RueckmeldungenController extends Controller
{

    public function updateDate(updateRueckmeldeDateRequest $request, Rueckmeldungen $rueckmeldung)
    {
        $rueckmeldung->update([
            'ende' => $request->date
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Rückmeldefrist wurde verlängert'
        ]);
    }


    public function create(Post $post, $type)
    {
        return view('nachrichten.createAbfrage', [
            'nachricht' => $post,
        ])->with([
            'type' => 'success',
            'Meldung' => 'Nachricht wurde erstellt',
        ]);
    }

    public function editAbfrage(Rueckmeldungen $rueckmeldung)
    {
        if ($rueckmeldung->userRueckmeldungen()->count() > 0) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Es wurden bereits Rückmeldungen gegeben'
            ]);
        }
        return view('nachrichten.editAbfrage', [
            'rueckmeldung' => $rueckmeldung
        ]);
    }

    public function updateAbfrage(createAbfrageRequest $request, Rueckmeldungen $rueckmeldung)
    {
        if ($rueckmeldung->userRueckmeldungen->count() > 0) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Es wurden bereits Rückmeldungen gegeben'
            ]);
        }

        $rueckmeldung->update($request->validated());
        $rueckmeldung->update([
            'max_answers' => $request->max_number
        ]);

        AbfrageOptions::where('rueckmeldung_id', $rueckmeldung->id)->delete();

        $options = [];
        foreach ($request->options as $key => $value) {
            if ($value != "") {
                $options[] = [
                    'rueckmeldung_id' => $rueckmeldung->id,
                    'type' => $request->types[$key],
                    'option' => $value,
                ];
            }

        }

        AbfrageOptions::insert($options);

        return redirect(url('/home#' . $rueckmeldung->post->id))->with([
            'type' => 'success',
            'Meldung' => 'Abfrage wurde geändert.'
        ]);
    }

    /**
     * Show all Rueckmeldungen
     */

    public function index()
    {
        if (!auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt'
            ]);
        }

        return view('rueckmeldungen.index', [
            'rueckmeldungen' => Rueckmeldungen::whereHas('post')->with('post')->withCount('userRueckmeldungen as rueckmeldungen')->orderByDesc('ende')->get()
        ]);
    }

    //zeigt alle Rückmeldungen zu einem Post
    public function show(Rueckmeldungen $rueckmeldung)
    {
        if (!auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt'
            ]);
        }

        return view('rueckmeldungen.show', [
            'rueckmeldungen' => $rueckmeldung->userRueckmeldungen()->orderByDesc('created_at')->get(),
            'rueckmeldung' => $rueckmeldung
        ]);
    }

    public function download(Rueckmeldungen $rueckmeldung, $user_id)
    {
        if (!auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt'
            ]);
        }

        $pdf = PDF::loadView('pdf.userRueckmeldungen', [
            'nachricht' => $rueckmeldung->post,
            'rueckmeldungen' => $rueckmeldung->userRueckmeldungen()->where('users_id', $user_id)->get()
        ]);

        return $pdf->download(Carbon::now()->format('Y-m-d') . '_Rückmeldung.pdf');
    }

    public function downloadAll(Rueckmeldungen $rueckmeldung)
    {
        if (!auth()->user()->can('manage rueckmeldungen')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt'
            ]);
        }

        switch ($rueckmeldung->type) {
            case 'email':
                //PDF für Text-Rückmeldungen
                $pdf = PDF::loadView('pdf.userRueckmeldungen', [
                    'nachricht' => $rueckmeldung->post,
                    'rueckmeldungen' => $rueckmeldung->userRueckmeldungen
                ]);
                break;
            case 'abfrage':
                return Excel::download(new AbfrageExport($rueckmeldung), Str::camel($rueckmeldung->text) . Carbon::now()->format('Ymd_Hi') . '.xlsx');

                break;
        }


        return $pdf->download(Carbon::now()->format('Y-m-d') . '_Nachrichten.pdf');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(createRueckmeldungRequest $request, $posts_id)
    {
        $rueckmeldung = new Rueckmeldungen($request->validated());
        $rueckmeldung->post_id = $posts_id;
        $rueckmeldung->save();

        return redirect()->to(url('/home'))->with([
            'type' => 'success',
            'Meldung' => 'Rückmeldung erstellt.',
        ]);
    }

    /**
     * Store a newly created Abfrage in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeAbfrage(createAbfrageRequest $request, $posts_id)
    {

        $rueckmeldung = new Rueckmeldungen($request->validated());
        $rueckmeldung->type = 'abfrage';
        $rueckmeldung->text = $request->description;
        $rueckmeldung->post_id = $posts_id;
        $rueckmeldung->max_answers = ($request->max_answers > 0) ? $request->max_answers : '0';
        $rueckmeldung->save();

        $post = Post::find($posts_id);


        $options = [];
        foreach ($request->options as $key => $value) {
            if ($value != "") {
                $options[] = [
                    'rueckmeldung_id' => $rueckmeldung->id,
                    'type' => $request->types[$key],
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
     * @param Request $request
     * @param Rueckmeldungen $rueckmeldungen
     * @return RedirectResponse
     */
    public function update(Request $request, $post_id)
    {
        $rueckmeldung = Rueckmeldungen::firstOrNew([
            'post_id'  => $post_id,
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
            'type'   => 'success',
            'Meldung'    => 'Rückmeldung gespeichert',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Rueckmeldungen $rueckmeldungen
     * @return JsonResponse
     */
    public function destroy(Rueckmeldungen $rueckmeldung)
    {
        $rueckmeldung->delete();

        return response()->json([
            'message' => 'Gelöscht',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Rueckmeldungen $rueckmeldungen
     * @return RedirectResponse
     */
    public function destroyRueckmeldung($rueckmeldungen)
    {
        $rueckmeldungen->delete();

        return redirect()->back()->with([
            'type'   => 'success',
            'Meldung'    => 'Rückmeldung gelöscht',
        ]);
    }

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
                        Mail::to($email)->send(new ErinnerungRuecklaufFehlt($User->email, $User->name, $Rueckmeldung->post->header, $Rueckmeldung->ende));
                    }
                }
            }
        }
    }

    public function updateCommentable(Rueckmeldungen $rueckmeldungen)
    {
        if ($rueckmeldungen->commentable) {
            $rueckmeldungen->update([
                'commentable'=>false,
            ]);
        } else {
            $rueckmeldungen->update([
                'commentable'=>true,
            ]);
        }

        return redirect()->back();
    }

    public function createImageRueckmeldung(Request $request,  $posts_id)
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
            'type'  => 'success',
            'Meldung'=>'Bild-Upload mit Kommentaren erstellt.',
        ]);
    }

    public function createDiskussionRueckmeldung(Request $request,  $posts_id)
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
            'type'  => 'success',
            'Meldung'=>'Diskussion erstellt.',
        ]);
    }
}
