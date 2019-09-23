<?php

namespace App\Http\Controllers;

use App\Model\Groups;
use App\Support\Collection;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\Models\Media;

class FileController extends Controller
{

    public function __construct()
    {
        $this->middleware('password_expired');
    }

    public function delete(Media $file){

        $file->delete();

        return response()->json([
            "message"   => "Gelöscht"
        ], 200);
    }


    public function index(){
        $user = auth()->user();
        $gruppen = $user->groups()->with('media')->get();






        if ($user->can('upload files')){

            return view('files.indexVerwaltung',[
                'gruppen' => $gruppen
            ]);


        } else{
            $media = new Collection();

            foreach ($gruppen as $gruppe){
                $gruppenMedien = $gruppe->getMedia();
                foreach ($gruppenMedien as $medium){
                    $media->push($medium);
                }
            }

            $media = $media->unique('name')->all();


            return view('files.index',[
                'gruppen' => $gruppen,
                "medien"  =>  $media
            ]);
        }


    }

    public function create(){
        return view('files.create',[
            'groups'    => Groups::all()
        ]);
    }

    public function store(Request $request){
        if (!auth()->user()->can('upload files')){
            return redirect('/home')->with([
                'type'   => "danger",
                "Meldung"    => "Berechtigung fehlt"
            ]);
        }

        $gruppen= $request->input('gruppen');

        if ($gruppen[0] == "all"){
            $gruppen = Groups::all();
        } else {
            $gruppen = Groups::find($gruppen);
        }

        if ($request->hasFile('files')) {

            foreach ($gruppen as $gruppe) {
                    $gruppe->addMediaFromRequest('files')
                        ->preservingOriginal()
                        ->toMediaCollection();
            }
        }
            return redirect('/files')->with([
                "type"  => "success",
                "Meldung"   => "Download erzeugt"
            ]);




    }
}
