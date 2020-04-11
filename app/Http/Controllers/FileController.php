<?php

namespace App\Http\Controllers;

use App\Mail\newFilesAddToPost;
use App\Model\Post;
use App\Repositories\GroupsRepository;
use App\Model\Group;
use App\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
                $gruppen = Group::where('protected', 0)->get();
            } else {
                $gruppen = Group::all();
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
            'groups'    => Group::all()
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

    public function saveFileRueckmeldung(Request $request, Post $posts){


            if ($request->hasFile('files')) {
            $posts->addAllMediaFromRequest(['files'])
                ->each(function ($fileAdder) use ($request) {
                    $fileAdder
                        ->usingName($request->name)
                        ->toMediaCollection('images');
                });

            @Mail::to($posts->autor->email)->queue(new newFilesAddToPost(auth()->user()->name, $posts->header));

        } else {
            return redirect(url('home/'))->with([
                "type"  => "warning",
                "Meldung"   => "Upload fehlgeschlagen"
            ]);
        }
            return redirect(url('home/#'.$posts->id))->with([
                "type"  => "success",
                "Meldung"   => "Bild erfolgreich hinzugefügt"
            ]);
    }
}
