<?php

namespace App\Http\Controllers;

use App\Http\Requests\createDiscussionRequest;
use App\Model\Comment;
use App\Model\Discussion;
use App\Model\Group;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 *
 */
class ElternratController extends Controller
{
    /**
     *
     */
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
        $Group = Group::where('name', '=', 'Elternrat')->first();


        $user = Role::findByName('Elternrat');
        $user = $user->users;

        $permission = Permission::findByName('view elternrat');
        $userPermission = $permission->users;

        foreach ($userPermission as $pushUser) {
            $user = $user->push($pushUser);
        }

        $themen->load('comments', 'comments.creator');

        return view('elternrat.index', [
            'themen' => $themen,
            'directories' => config('app.directories_elternrat'),
            'users' => $user->sortBy('name'),
            'group' => $Group,
        ]);
    }


    /**
     * show view for creating new discussion
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('elternrat.createDiscussion');
    }


    /**
     * store new discussion
     *
     * @param createDiscussionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(createDiscussionRequest $request)
    {
        $Discussion = new Discussion([
            'header' => $request->header,
            'text' => $request->text,
            'owner' => $request->user()->id,
            'sticky' => $request->sticky,
        ]);

        $Discussion->save();

        return redirect()->to(url('elternrat'))->with([
            'type' => 'success',
            'meldung' => 'Beitrag erstellt',
        ]);
    }


    /**
     *
     * show view to edit the given discussion
     *
     * @param Discussion $discussion
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Discussion $discussion)
    {
        return  view('elternrat.editDiscussion', [
            'beitrag' => $discussion,
        ]);
    }


    /**
     * Update the Ressource
     *
     * @param createDiscussionRequest $request
     * @param Discussion $discussion
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(createDiscussionRequest $request, Discussion $discussion)
    {
        $discussion->update($request->validated());

        return redirect()->to(url('elternrat'))->with([
            'type' => 'success',
            'Meldung' => 'Änderungen gespeichert',
        ]);
    }


    /**
     *
     * delete the given Media
     *
     * @param Request $request
     * @param Media $file
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFile(Request $request, Media $file)
    {
        if ($request->user()->can('delete elternrat file')) {
            $file->delete();

            return response()->json([
                'message' => 'Gelöscht',
            ], 200);
        }

        return response()->json([
            [
                'message' => 'Berechtigung fehlt',
            ], 400,
        ]);
    }

    /**
     *
     * Show view to add new file
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function addFile()
    {
        return view('elternrat.createFile', [
            'groups' => Group::all(),
        ]);
    }

    /**
     *
     * Add new File
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFile(Request $request)
    {
        $gruppe = Group::where('name', 'Elternrat')->first();

        if ($request->hasFile('files')) {
            $gruppe->addMediaFromRequest('files')
                    ->preservingOriginal()
                    ->toMediaCollection($request->directory);
        }

        return redirect()->to(url('elternrat'))->with([
            'type' => 'success',
            'Meldung' => 'Datei gespeichert',
        ]);
    }

    /**
     * Store the new Comment
     *
     * @param Discussion $discussion
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeComment(Discussion $discussion, Request $request)
    {
        if ($request->body != '') {
            $discussion->comment([
                'body' => $request->input('body'),
            ], $request->user());
        }

        return redirect()->back();
    }

    /**
     * Delete the given comment
     * @param Comment $comment
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function deleteComment(Comment $comment)
    {
        $comment->delete();

        return response('Gelöscht');
    }
}
