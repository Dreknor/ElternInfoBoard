<?php

namespace App\Http\Controllers;

use App\Http\Requests\createDiscussionRequest;
use App\Model\Comment;
use App\Model\Discussion;
use App\Model\Groups;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use function GuzzleHttp\Promise\all;

class ElternratController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view elternrat']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $themen = Discussion::query()->orderbyDesc('sticky')->orderbyDesc('updated_at')->paginate(15);
        $Group = Groups::where('name', '=', 'Elternrat')->first();
        $files = $Group->getMedia();

        $user = Role::findByName('Elternrat');
        $user = $user->users;

        $permission = Permission::findByName('view elternrat');
        $userPermission = $permission->users;

        foreach ($userPermission as $pushUser){
           $user = $user->push($pushUser);
        }


        $themen->load('comments', 'comments.creator');

        return view('elternrat.index', [
            'themen'    => $themen,
            'files'     => $files,
            'users'     => $user->sortBy('name')
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('elternrat.createDiscussion');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(createDiscussionRequest $request)
    {
        $Discussion = new Discussion([
            'header'    => $request->header,
            'text'      => $request->text,
            'owner'     => auth()->user()->id,
            'sticky'    => $request->sticky
        ]);

        $Discussion->save();

        return redirect(url('elternrat'))->with([
            'type'  => "success",
            'meldung'   => "Beitrag erstellt"
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\discussion  $discussion
     * @return \Illuminate\Http\Response
     */
    public function show(discussion $discussion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\discussion  $discussion
     * @return \Illuminate\Http\Response
     */
    public function edit(Discussion $discussion)
    {
        return  view("elternrat.editDiscussion", [
            "beitrag"   => $discussion
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\discussion  $discussion
     * @return \Illuminate\Http\Response
     */
    public function update(createDiscussionRequest $request, Discussion $discussion)
    {
        $discussion->update($request->all());
        return redirect(url('elternrat'))->with([
           'type'   => "success",
           "Meldung"    =>  "Änderungen gespeichert"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\discussion  $discussion
     * @return \Illuminate\Http\Response
     */
    public function destroy(discussion $discussion)
    {
        //
    }

    public function deleteFile(Media $file){

        $file->delete();

        return response()->json([
            "message"   => "Gelöscht"
        ], 200);
    }

    public function addFile(){
        return view('elternrat.createFile',[
            'groups'    => Groups::all()
        ]);
    }

    public function storeFile(Request $request){

            $gruppe = Groups::where('name', "Elternrat")->first();

        if ($request->hasFile('files')) {
                $gruppe->addMediaFromRequest('files')
                    ->preservingOriginal()
                    ->toMediaCollection();
        }
        return redirect(url('elternrat'))->with([
            "type"  => "success",
            "Meldung"   => "Datei gespeichert"
        ]);
    }

    public function storeComment(Discussion $discussion, Request $request){

        if ($request->body != ""){
            $discussion->comment([
                'body' => $request->input('body')
            ], auth()->user());
        }

        return redirect()->back();
    }

    public function deleteComment(Comment $comment){
        $comment->delete();

        return response('Gelöscht', 200);
    }
}
