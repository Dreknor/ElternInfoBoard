<?php

namespace App\Http\Controllers;

use App\Http\Requests\createRueckmeldungRequest;
use App\Mail\ErinnerungRuecklaufFehlt;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RueckmeldungenController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return RedirectResponse
     */
    public function store(createRueckmeldungRequest $request, $posts_id)
    {
        $rueckmeldung = new Rueckmeldungen($request->all());
        $rueckmeldung->post_id = $posts_id;
        $rueckmeldung->save();

        $post = Post::find($posts_id);

        if ($rueckmeldung->ende->greaterThan($post->archiv_ab)) {
            $post->update([
               'archiv_ab' => $rueckmeldung->ende,
            ]);
        }

        return redirect()->to(url('/home'))->with([
           'type'   => 'success',
           'Meldung'    => 'Rückmeldung erstellt.',
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Rueckmeldungen  $rueckmeldungen
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
     * @param  \App\Model\Rueckmeldungen  $rueckmeldungen
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
     * @param  \App\Model\Rueckmeldungen  $rueckmeldungen
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
                        Log::info($email);
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

    public function createImageRueckmeldung(Request $request, Post $posts)
    {
        $rueckmeldung = new Rueckmeldungen([
            'posts_id'  => $posts->id,
            'type'  => 'bild',
            'commentable'  => 1,
            'empfaenger'  => $request->user()->email,
            'ende'      => $posts->archiv_ab,
            'text'      => ' ',
        ]);
        $rueckmeldung->save();

        return redirect()->back()->with([
            'type'  => 'success',
            'Meldung'=>'Bild-Upload mit Kommentaren erstellt.',
        ]);
    }
}
