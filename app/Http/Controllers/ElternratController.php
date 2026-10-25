<?php

namespace App\Http\Controllers;

use App\Http\Requests\createDiscussionRequest;
use App\Model\Comment;
use App\Model\Discussion;
use App\Model\Group;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ElternratController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            ['permission:view elternrat'],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $query = Discussion::query();

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('header', 'like', "%{$searchTerm}%")
                    ->orWhere('text', 'like', "%{$searchTerm}%");
            });
        }

        // Filter functionality
        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'sticky':
                    $query->where('sticky', 1);
                    break;
                case 'my':
                    $query->where('owner', $request->user()->id);
                    break;
                case 'all':
                default:
                    // No additional filtering needed
                    break;
            }
        }

        // Default sorting
        $themen = $query->orderByDesc('sticky')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->appends($request->only(['search', 'filter']));

        $Group = Group::where('name', '=', 'Elternrat')->first();

        $user = Role::findByName('Elternrat');
        $user = $user->users;

        $permission = Permission::findByName('view elternrat');

        foreach ($permission->users as $pushUser) {
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
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('elternrat.createDiscussion');
    }

    /**
     * Display the specified resource (redirect to index)
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function show($id)
    {
        return redirect()->route('elternrat.index');
    }

    /**
     * store new discussion
     *
     * @return RedirectResponse
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
     * delete the given discussion
     *
     * @return RedirectResponse
     */
    public function destroy(Discussion $discussion)
    {

        if (auth()->user()->can('delete elternrat file')) {
            $discussion->comments()->delete();
            $discussion->delete();

            return redirect()->to(url('elternrat'))->with([
                'type' => 'success',
                'meldung' => 'Beitrag gelöscht',
            ]);
        }

        return redirect()->to(url('elternrat'))->with([
            'type' => 'danger',
            'meldung' => 'Berechtigung fehlt',
        ]);
    }

    /**
     * show view to edit the given discussion
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Discussion $discussion)
    {
        $this->authorizeDiscussion($discussion);

        return view('elternrat.editDiscussion', [
            'beitrag' => $discussion,
        ]);
    }

    /**
     * Update the Ressource
     *
     * @return RedirectResponse
     */
    public function update(createDiscussionRequest $request, Discussion $discussion)
    {
        $this->authorizeDiscussion($discussion);

        $discussion->update($request->validated());

        return redirect()->to(url('elternrat'))->with([
            'type' => 'success',
            'Meldung' => 'Änderungen gespeichert',
        ]);
    }

    /**
     * delete the given Media
     *
     * @return JsonResponse
     */
    public function deleteFile(Request $request, Media $file)
    {
        // Nur Dateien des Elternrats (vorher jede beliebige Mediendatei der Instanz)
        $elternrat = Group::where('name', 'Elternrat')->first();
        $isElternratFile = ($elternrat && $file->model_type === Group::class && (int) $file->model_id === $elternrat->id)
            || $file->model_type === Discussion::class;

        if ($isElternratFile && $request->user()->can('delete elternrat file')) {
            $file->delete();

            return response()->json([
                'message' => 'Gelöscht',
            ]);
        }

        return response()->json([
            [
                'message' => 'Berechtigung fehlt',
            ], 400,
        ]);
    }

    /**
     * Show view to add new file
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function addFile()
    {
        return view('elternrat.createFile', [
            'groups' => Group::active()->get(),
        ]);
    }

    /**
     * Add new File
     *
     * @return RedirectResponse
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
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
     * @return RedirectResponse
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
     *
     * @return Application|ResponseFactory|Response
     */
    public function deleteComment(Comment $comment)
    {
        // Nur Kommentare zu Elternrats-Beiträgen, nur eigene oder mit Löschrecht (vorher jeder Kommentar, auch zu Nachrichten)
        abort_unless($comment->commentable_type === Discussion::class, 404);
        abort_unless((int) $comment->creator_id === auth()->id() || auth()->user()->can('delete elternrat file'), 403);

        $comment->delete();

        return response('Gelöscht');
    }

    /**
     * Subscribe to discussion notifications
     */
    public function subscribe(Discussion $discussion, Request $request)
    {
        \App\Model\DiscussionSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'discussion_id' => $discussion->id,
            ],
            [
                'email_notifications' => true,
                'web_notifications' => true,
            ]
        );

        return back()->with([
            'type' => 'success',
            'meldung' => 'Benachrichtigungen aktiviert',
        ]);
    }

    /**
     * Unsubscribe from discussion notifications
     */
    public function unsubscribe(Discussion $discussion, Request $request)
    {
        \App\Model\DiscussionSubscription::where('user_id', $request->user()->id)
            ->where('discussion_id', $discussion->id)
            ->delete();

        return back()->with([
            'type' => 'success',
            'meldung' => 'Benachrichtigungen deaktiviert',
        ]);
    }

    /** Beiträge bearbeiten nur Verfasser bzw. Personen mit Löschrecht im Elternrat. */
    private function authorizeDiscussion(Discussion $discussion): void
    {
        abort_unless((int) $discussion->owner === auth()->id() || auth()->user()->can('delete elternrat file'), 403, 'Berechtigung fehlt');
    }
}
