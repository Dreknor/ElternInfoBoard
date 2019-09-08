<?php

namespace App\Http\Controllers;

use App\Http\Requests\createNachrichtRequest;
use App\Model\groups;
use App\Model\posts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;

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
    public function index()
    {

        $Nachrichten = auth()->user()->posts;
        $Nachrichten =$Nachrichten->unique()->sortByDesc('updated_at')->paginate(20);

        $Nachricht=$Nachrichten->first();

        //$Nachricht->addMedia(Storage::disk('local')->path('EinladungKinderstadt.pdf'))->toMediaCollection('files');

        return view('home', [
            "nachrichten"   => $Nachrichten
        ]);
    }

    public function create(){

        if (!auth()->user()->can('create posts')){
            return redirect('/home')->with([
               'type'   => "danger",
               "Meldung"    => "Berechtigung fehlt"
            ]);
        }

        $gruppen = groups::all();

        return view('nachrichten.create',[
            'gruppen'   => $gruppen
        ]);
    }

    public function store(createNachrichtRequest $request){

        $post = new posts($request->all());
        $post->save();

        $gruppen= $request->input('gruppen');

        if ($gruppen[0] == "all"){
            $gruppen = groups::all();
        } else {
            $gruppen = groups::find($gruppen);
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

        return redirect('/home')->with([
            "type"  => "success",
            "Meldung"   => "Nachricht angelegt"
        ]);



    }
}
