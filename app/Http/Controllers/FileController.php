<?php

namespace App\Http\Controllers;

use App\Model\Posts;
use App\Repositories\GroupsRepository;
use App\Model\Groups;
use App\Support\Collection;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\Models\Media;

class FileController extends Controller
{

        public function __construct(GroupsRepository $groupsRepository)
        {
            $this->middleware('password_expired');
            $this->grousRepository = $groupsRepository;
        }

    public function delete(Media $file){

        $file->delete();

        return response()->json([
            "message"   => "Gelöscht"
        ], 200);
    }


    public function index(){
        $user = auth()->user()->load('groups');

        if ($user->can('upload files')){
            if (!$user->can('view protected')){
                $gruppen = Groups::where('protected', 0)->get();
            } else {
                $gruppen = Groups::all();
            }

            return view('files.indexVerwaltung',[
                'gruppen' => $gruppen->load('media')
            ]);


        } else{

            $gruppen = $user->groups()->with('media')->get();
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
        $gruppen = $this->grousRepository->getGroups($gruppen);

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

    public function saveFileRueckmeldung(Request $request, Posts $posts){
        if ($request->hasFile('files')) {
            $posts->addAllMediaFromRequest(['files'])
                ->each(function ($fileAdder) {
                    $fileAdder
                        ->toMediaCollection('images');
                });
        } else {
            return redirect(url('home/'))->with([
                "type"  => "warning",
                "Meldung"   => "upload fehlgeschlagen"
            ]);
        }
            return redirect(url('home/#'.$posts->id))->with([
                "type"  => "success",
                "Meldung"   => "Bild erfolgreich hinzugefügt"
            ]);
    }
}
