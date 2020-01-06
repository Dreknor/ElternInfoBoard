<?php

namespace App\Http\Controllers;

use App\Http\Requests\createNachrichtRequest;
use App\Http\Requests\editPostRequest;
use App\Mail\AktuelleInformationen;
use App\Mail\DringendeInformationen;
use App\Mail\dringendeNachrichtStatus;
use App\Mail\newUnveroeffentlichterBeitrag;
use App\Model\Groups;
use App\Model\Posts;
use App\Model\Reinigung;
use App\Model\Rueckmeldungen;
use App\Model\Termin;
use App\Model\User;
use App\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;

class NachrichtenController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index($archiv = null)
    {

        $user = auth()->user();
        $user->with(['userRueckmeldung', 'sorgeberechtigter2', 'sorgeberechtigter2.userRueckmeldung', 'sorgeberechtigter2.listen_eintragungen','termine', 'listen_eintragungen']);

        $sorg2 = $user->sorgeberechtigter2;

        $archivDate = Carbon::now()->endOfDay()->subWeeks(1);

        if (!$user->can('edit posts')) {
            $Nachrichten = $user->posts()->with('media', 'autor', 'groups', 'rueckmeldung')->get();

            if ($archiv) {
                $Nachrichten = $user->posts()->whereDate('archiv_ab', '<=', Carbon::now()->startOfDay())->whereDate('archiv_ab', '>', $user->created_at)->with('media', 'autor', 'groups', 'rueckmeldung')->get();

                if ($user->can('create posts')){
                    $eigenePosts = Posts::query()->where('author', $user->id)->whereDate('archiv_ab', '<=', Carbon::now()->startOfDay())->get();
                    $Nachrichten = $Nachrichten->concat($eigenePosts);
                }

            } else {
                $Nachrichten = $user->posts()->whereDate('archiv_ab', '>', Carbon::now()->startOfDay())->whereDate('archiv_ab', '>', $user->created_at)->with('media', 'autor', 'groups', 'rueckmeldung')->get();

                if ($user->can('create posts')){
                    $eigenePosts = Posts::query()->where('author', $user->id)->whereDate('archiv_ab', '>', Carbon::now()->startOfDay())->get();
                    $Nachrichten = $Nachrichten->concat($eigenePosts);
                }
            }

            $Reinigung = Reinigung::whereIn('id', [$user->id, $user->sorg2])->whereBetween('datum', [Carbon::now()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()])->first();


        } else {

            $Reinigung = Reinigung::whereIn('users_id', [$user->id, $user->sorg2])->whereBetween('datum', [Carbon::now()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()])->first();

            if ($archiv) {
                $Nachrichten = Posts::whereDate('archiv_ab', '<=', Carbon::now()->startOfDay())->with('media', 'autor', 'groups', 'rueckmeldung')->get()->sortByDesc('updated_at')->unique()->paginate(30);

            } else {
                $Nachrichten = Posts::whereDate('archiv_ab', '>', Carbon::now()->startOfDay())->with('media', 'autor', 'groups', 'rueckmeldung')->get();
            }

        }

        $Nachrichten = $Nachrichten->unique('id')->sortByDesc('updated_at')->paginate(30);



        //Termine holen
        if (!$user->can('edit termin')) {
            $Termine = $user->termine->sortBy('start');
        } else {
            $Termine = Termin::all();
            $Termine = $Termine->unique('id');
        }

        $Termine = $Termine->sortBy('start');


        //Termine aus Listen holen
        $listen_termine = $user->listen_eintragungen()->whereDate('termin', '>', Carbon::now()->startOfDay())->get();

        //Ergänze Listeneintragungen
        if (count($listen_termine) > 0) {
            foreach ($listen_termine as $termin) {
                $newTermin = new Termin([
                    "terminname" => $termin->liste->listenname,
                    "start" => $termin->termin,
                    "ende" => $termin->termin->copy()->addMinutes($termin->liste->duration),
                    "fullDay" => null
                ]);
                $Termine->push($newTermin);
            }
        }

        //Listentermine von Sorg2
        if (!is_null($sorg2)){
            foreach ($sorg2->listen_eintragungen()->whereDate('termin', '>', Carbon::now()->startOfDay())->get() as $termin) {
                $newTermin = new Termin([
                    "terminname" => $termin->liste->listenname,
                    "start" => $termin->termin,
                    "ende" => $termin->termin->copy()->addMinutes($termin->liste->duration),
                    "fullDay" => null
                ]);
                $Termine->push($newTermin);
            }
        }

        $Termine = $Termine->unique('id');
        $Termine = $Termine->sortBy('start');


        return view('home', [
            "nachrichten" => $Nachrichten,
            'datum'     => Carbon::now(),
            "archiv" => $archiv,
            "user" => $user,
            "gruppen" => Groups::all(),
            "Reinigung" => $Reinigung,
            'termine' => $Termine
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function create()
    {

        if (!auth()->user()->can('create posts')) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
            ]);
        }

        $gruppen = Groups::all();

        return view('nachrichten.create', [
            'gruppen' => $gruppen
        ]);
    }

    /**
     * @param Posts $posts
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function edit(Posts $posts)
    {
        if (!auth()->user()->can('edit posts') and auth()->user()->id != $posts->author) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
            ]);
        }

        $gruppen = Groups::all();

        if (is_null($posts->rueckmeldung)) {
            $rueckmeldung = new Rueckmeldungen();
        } else {
            $rueckmeldung = $posts->rueckmeldung;
        }

        return view('nachrichten.edit', [
            'gruppen' => $gruppen,
            "post" => $posts,
            'rueckmeldung' => $rueckmeldung
        ]);
    }

    /**
     * @param createNachrichtRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function store(createNachrichtRequest $request)
    {

        $user = auth()->user();

        if (!auth()->user()->can('create posts')) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
            ]);
        } elseif ($request->has('urgent') and $request->input('urgent') == 1 and (!$user->can('send urgent message') or !Hash::check($request->input('password'), $user->password))) {
            return redirect()->back()->withInput($request->all())->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt für dringende Nachrichten oder Passwort ist falsch"
            ]);
        }

        $post = new Posts($request->all());


        $post->author = auth()->user()->id;
        $post->save();

        $gruppen = $request->input('gruppen');

        if ($gruppen[0] == "all") {
            $gruppen = Groups::all();
        } elseif ($gruppen[0] == 'Grundschule' or $gruppen[0] == 'Oberschule') {
            $gruppen = Groups::whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen)->get();
            $gruppen = $gruppen->unique();
        } else {
            $gruppen = Groups::find($gruppen);
        }


        $post->groups()->attach($gruppen);

        if (!auth()->user()->can('release posts')){
            $permission = Permission::query()->where('name', 'release posts')->first();

            foreach ($permission->users as $user){
                Mail::to($user->email)->queue(new newUnveroeffentlichterBeitrag(auth()->user()->name, $post->header));
            }
        }

        //Dateien verarbeiten
        if ($request->hasFile('files')) {
            if (auth()->user()->can('upload great files')) {
                @ini_set('upload_max_size', '300M');
                @ini_set('post_max_size', '300M');
            }

            if ($request->input('collection') == 'files') {
                $post->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('files');
                    });
            } else {
                $post->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('images');
                    });
            }
        }


        $Meldung = "Nachricht wurde erstellt.";

        //Versenden dringender Nachrichten
        if ($request->has('urgent') and $request->input('urgent') == 1 and $user->can('send urgent message') and Hash::check($request->input('password'), $user->password)) {
            $gruppen->load('users');

            Log::debug($gruppen);

            $users = new Collection();

            foreach ($gruppen as $gruppe) {
                $newusers = $users->merge($gruppe->users);
            }


            Log::debug($newusers);

            $newusers = $newusers->unique('email');

            Log::debug($newusers);

            $sendTo = [];
            foreach ($newusers as $mailUser) {

                $header = $post->header;
                $news = $post->news;
                @Mail::to($mailUser->email)->queue(new DringendeInformationen("$header", "$news"));
                $sendTo[]= [
                  "name"    => $mailUser->name,
                  "email"   => $mailUser->email
                ];
            }

            @Mail::to(auth()->user()->email)->send(new dringendeNachrichtStatus($sendTo));
            $Meldung = "Es wurden ".count($sendTo)." Benutzer per Mail benachrichtigt.";

        }

        //Umleitung bei Rückmeldungsbedarf
        if ($request->input('rueckmeldung') == 0) {
            return redirect(url('/home#' . $post->id))->with([
                "type" => "success",
                "Meldung" => "Nachricht angelegt."
            ]);
        }

        return view('nachrichten.createRueckmeldung', [
            "nachricht" => $post
        ])->with([
            "type" => "success",
            "Meldung" => $Meldung
        ]);


    }

    /**
     * @param Posts $posts
     * @param editPostRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(Posts $posts, editPostRequest $request)
    {

        $user = auth()->user();

        if (!auth()->user()->can('edit posts') and auth()->user()->id != $posts->author) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
            ]);
        }

        $posts->fill($request->all());
        //$posts->author = auth()->user()->id;

        $posts->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s', $request->input('updated_at'));
        $posts->save();


        $gruppen = $request->input('gruppen');

        if ($gruppen[0] == "all") {
            $gruppen = Groups::all();
        } elseif ($gruppen[0] == 'Grundschule' or $gruppen[0] == 'Oberschule') {
            $gruppen = Groups::whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen)->get();
            $gruppen = $gruppen->unique();
        } else {
            $gruppen = Groups::find($gruppen);
        }

        $posts->groups()->detach();
        $posts->groups()->attach($gruppen);

        if ($request->hasFile('files')) {
            if ($request->input('collection') == 'files') {
                $posts->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('files');
                    });

            } else {
                $posts->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('images');
                    });
            }


        }

        $Meldung ="Nachricht bearbeitet";

        if ($request->has('urgent') and $request->input('urgent') == 1 and $user->can('send urgent message')) {
            if (!Hash::check($request->input('password'), $user->password)) {
                return redirect()->back()->with([
                    "type" => "danger",
                    "Meldung" => "Passwort falsch"
                ]);
            }
            $gruppen->load('users');

            $users = new Collection();
            foreach ($gruppen as $gruppe) {
                $newusers = $users->merge($gruppe->users);
            }

            $newusers = $newusers->unique('email');
            Log::info('users for Mail', [$newusers]);
            $sendTo = [];

            foreach ($newusers as $mailUser) {
                $header = $posts->header;
                $news = $posts->news;
                @Mail::to($mailUser->email)->queue(new DringendeInformationen("$header", "$news"));

                $sendTo[]= [
                    "name"    => $mailUser->name,
                    "email"   => $mailUser->email
                ];

                @Mail::to(auth()->user()->email)->send(new dringendeNachrichtStatus($sendTo));
                $Meldung = "Es wurden ".count($sendTo)." Benutzer per Mail benachrichtigt.";
            }



        }

        return redirect(url('/home#' . $posts->id))->with([
            "type" => "success",
            "Meldung" => $Meldung
        ]);

    }

    /**
     *
     */
    public function email()
    {

        $users = User::where('benachrichtigung', 'weekly')->get();

        foreach ($users as $user) {

            if (!$user->can('edit posts')) {
                $Nachrichten = $user->posts;

            } else {

                $Nachrichten = Posts::all();

            }
            //$Nachrichten->unique('id')->sortByDesc('updated_at');

            $Nachrichten = $Nachrichten->filter(function ($post) use ($user) {
                if ($post->released == 1 and $post->updated_at->greaterThanOrEqualTo($user->lastEmail) and $post->archiv_ab->greaterThan(Carbon::now()) ) {
                    return $post;
                }
            })->unique()->sortByDesc('updated_at')->all();


            if (count($Nachrichten) > 0) {
                Mail::to($user->email)->queue(new AktuelleInformationen($Nachrichten, $user->name));
                $user->lastEmail = Carbon::now();
                $user->save();
            }


        }
    }

    /**
     *
     */
    public function emailDaily()
    {
/*
        $users = User::where('benachrichtigung', 'daily')->with('posts')->get();

        foreach ($users as $user) {

            $Nachrichten = $user->posts;

            $Nachrichten = $Nachrichten->filter(function ($post) use ($user) {
                if ($post->released == 1 and $post->updated_at->greaterThanOrEqualTo($user->lastEmail)) {
                    return $post;
                }
            })->unique()->sortByDesc('updated_at')->all();

            if (count($Nachrichten) > 0) {
                Mail::to($user->email)->queue(new AktuelleInformationen($Nachrichten, $user->name));
                $user->lastEmail = Carbon::now();
                $user->save();
            }
        }
*/
    }


    /**
     * @param Posts $posts
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function touch(Posts $posts)
    {

        if ($posts->archiv_ab->lessThan(Carbon::now()->subWeeks(3))) {
            $newPost = $posts->duplicate();
            $newPost->archiv_ab = Carbon::now()->addWeek();
            $newPost->save();

        } else {
            $posts->archiv_ab = Carbon::now()->addWeek();
            $posts->save();

        }


        return redirect(url('/'));
    }

    /**
     * @param Posts $posts
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function release(Posts $posts)
    {
        if (!auth()->user()->can('release posts')) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
            ]);
        }

        $posts->updated_at = $posts->updated_at;
        $posts->released = 1;
        $posts->save();

        return redirect(url('/home#' . $posts->id))->with([
            "type" => "success",
            "Meldung" => "Nachricht veröffentlicht"
        ]);
    }

    /**
     * @param null $archiv
     * @return mixed
     */
    public function pdf($archiv = null)
    {
        $user = auth()->user();
        $user->with(['userRueckmeldung', 'sorgeberechtigter2', 'sorgeberechtigter2.userRueckmeldung']);
        $archivDate = Carbon::now()->endOfDay()->subWeeks(1);

        if (!$user->can('create posts')) {
            $Nachrichten = $user->posts()->with('media', 'autor', 'groups', 'rueckmeldung')->get();

            if ($archiv) {


                $Nachrichten = $Nachrichten->filter(function ($nachricht) use ($archivDate) {
                    return $nachricht->updated_at->lessThan($archivDate);
                });

                /*
                $Nachrichten = $Nachrichten->filter(function ($nachricht){
                    $date = Carbon::createFromFormat('d.m.', '31.7.');
                    if (Carbon::now()->month < 7){
                        $date->subYear();
                    }

                    return $nachricht->updated_at->greaterThan($date);
                });

                */

            } else {
                $Nachrichten = $Nachrichten->filter(function ($nachricht) use ($archivDate) {
                    return $nachricht->updated_at->greaterThanOrEqualTo($archivDate);
                });

            }

            $Nachrichten = $Nachrichten->unique()->sortByDesc('updated_at')->paginate(30);
        } else {


            if ($archiv) {
                $Nachrichten = Posts::with('media', 'autor', 'groups', 'rueckmeldung')->get();
                $Nachrichten = $Nachrichten->filter(function ($nachricht) use ($archivDate) {
                    return $nachricht->updated_at->lessThan($archivDate);
                })->sortByDesc('updated_at')->unique()->paginate(30);

            } else {
                $Nachrichten = Posts::with('media', 'autor', 'groups', 'rueckmeldung')->get();
                $Nachrichten = $Nachrichten->filter(function ($nachricht) use ($archivDate) {
                    return $nachricht->updated_at->greaterThanOrEqualTo($archivDate);
                })->sortByDesc('updated_at')->unique()->paginate(30);

            }

        }

        $pdf = \PDF::loadView('pdf.pdf', [
            "nachrichten" => $Nachrichten
        ]);
        return $pdf->download(\Carbon\Carbon::now()->format('Y-m-d') . '_Nachrichten.pdf');

    }

    /**
     * @param Posts $posts
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Posts $posts)
    {
        if ($posts->author == auth()->user()->id) {

            $posts->groups()->detach();
            if (!is_null($posts->rueckmeldung())) {
                $posts->rueckmeldung()->delete();
            }
            $posts->delete();

            return response()->json([
                "message" => "Gelöscht"
            ], 200);
        }

        return response()->json([
            "message" => "Berechtigung fehlt"
        ], 401);
    }
}
