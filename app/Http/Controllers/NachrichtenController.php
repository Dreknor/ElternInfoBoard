<?php

namespace App\Http\Controllers;

use App\Http\Requests\createNachrichtRequest;
use App\Http\Requests\editPostRequest;
use App\Jobs\SendEmailJob;
use App\Mail\AktuelleInformationen;
use App\Model\Groups;
use App\Model\Posts;
use App\Model\Rueckmeldungen;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

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
        $user->with(['userRueckmeldung']);

        if (!$user->can('edit posts')) {
            $Nachrichten = $user->posts()->with('media', 'autor', 'groups', 'rueckmeldung')->get();

            if ($archiv){

                $Nachrichten = $Nachrichten->filter(function ($nachricht){
                    return $nachricht->updated_at->lessThan(Carbon::now()->subWeeks(2));
                });
                $Nachrichten = $Nachrichten->filter(function ($nachricht){
                    $date = Carbon::createFromFormat('d.m.', '31.7.');
                    if (Carbon::now()->month < 7){
                        $date->subYear();
                    }

                    return $nachricht->updated_at->greaterThan($date);
                });

            } else {
                $Nachrichten = $Nachrichten->filter(function ($nachricht){
                    return $nachricht->updated_at->greaterThanOrEqualTo(Carbon::now()->subWeeks(2));
                });

            }

            $Nachrichten =$Nachrichten->unique()->sortByDesc('updated_at')->paginate(20);
        } else {



            if ($archiv){
                $Nachrichten = Posts::with('media', 'autor', 'groups', 'rueckmeldung')->get();
                $Nachrichten = $Nachrichten->filter(function ($nachricht){
                    return $nachricht->updated_at->lessThan(Carbon::now()->subWeeks(2));
                })->sortByDesc('updated_at')->unique()->paginate(20);

            } else {
                $Nachrichten = Posts::with('media', 'autor', 'groups', 'rueckmeldung')->get();
                $Nachrichten = $Nachrichten->filter(function ($nachricht){
                    return $nachricht->updated_at->greaterThanOrEqualTo(Carbon::now()->subWeeks(2));
                })->sortByDesc('updated_at')->unique()->paginate(20);

            }

        }


        return view('home', [
            "nachrichten"   => $Nachrichten,
            "archiv"    => $archiv,
            "user"      => $user
        ]);
    }

    public function create(){

        if (!auth()->user()->can('create posts')){
            return redirect('/home')->with([
               'type'   => "danger",
               "Meldung"    => "Berechtigung fehlt"
            ]);
        }

        $gruppen = Groups::all();

        return view('nachrichten.create',[
            'gruppen'   => $gruppen
        ]);
    }

    public function edit(Posts $posts){
        if (!auth()->user()->can('edit posts')){
            return redirect('/home')->with([
                'type'   => "danger",
                "Meldung"    => "Berechtigung fehlt"
            ]);
        }

        $gruppen = Groups::all();

        if (is_null($posts->rueckmeldung)){
            $rueckmeldung = new Rueckmeldungen();
        } else {
            $rueckmeldung = $posts->rueckmeldung;
        }

        return view('nachrichten.edit', [
            'gruppen'   => $gruppen,
            "post"      => $posts,
            'rueckmeldung'  => $rueckmeldung
        ]);
    }

    public function store(createNachrichtRequest $request){

        if (!auth()->user()->can('create posts')){
            return redirect('/home')->with([
                'type'   => "danger",
                "Meldung"    => "Berechtigung fehlt"
            ]);
        }

        $post = new Posts($request->all());

        $post->author = auth()->user()->id;
        $post->save();

        $gruppen= $request->input('gruppen');

        if ($gruppen[0] == "all"){
            $gruppen = Groups::all();
        } else {
            $gruppen = Groups::find($gruppen);
        }

        $post->groups()->attach($gruppen);


        if ($request->hasFile('files')) {
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

        if ($request->input('rueckmeldung') == 0){
            return redirect('/home')->with([
                "type"  => "success",
                "Meldung"   => "Nachricht angelegt"
            ]);
        }

        return view('nachrichten.createRueckmeldung',[
           "nachricht"  => $post
        ]);
    }

    public function update(Posts $posts, editPostRequest $request){

        if (!auth()->user()->can('edit posts')){
            return redirect('/home')->with([
                'type'   => "danger",
                "Meldung"    => "Berechtigung fehlt"
            ]);
        }

        $posts->fill($request->all());
        $posts->author = auth()->user()->id;

        $posts->save();


        $gruppen= $request->input('gruppen');

        if ($gruppen[0] == "all"){
            $gruppen = Groups::all();
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

        return redirect('/home')->with([
            "type"  => "success",
            "Meldung"   => "Nachricht bearbeitet"
        ]);

    }

    public function email(){

        $users = User::where('benachrichtigung', 'weekly')->with('posts')->get();

        foreach ($users as $user){

            $Nachrichten = $user->posts;

            $Nachrichten = $Nachrichten->filter(function ($post) use ($user){
                if ($post->released == 1 and $post->updated_at->greaterThanOrEqualTo($user->lastEmail)){
                    return $post;
                }
            })->unique()->sortByDesc('updated_at')->all();



            if (count($Nachrichten)>0){
                Mail::to($user->email)->queue(new AktuelleInformationen($Nachrichten, $user->name));
                $user->lastEmail = Carbon::now();
                $user->save();
            }
        }
    }
    public function emailDaily(){

        $users = User::where('benachrichtigung', 'daily')->with('posts')->get();

        foreach ($users as $user){

            $Nachrichten = $user->posts;

            $Nachrichten = $Nachrichten->filter(function ($post) use ($user){
                if ($post->released == 1 and $post->updated_at->greaterThanOrEqualTo($user->lastEmail)){
                    return $post;
                }
            })->unique()->sortByDesc('updated_at')->all();

            if (count($Nachrichten)>0){
                Mail::to($user->email)->queue(new AktuelleInformationen($Nachrichten, $user->name));
                $user->lastEmail = Carbon::now();
                $user->save();
            }
        }
    }

    public function touch(Posts $posts){
        $posts->touch();

        return redirect()->back();
    }
}
