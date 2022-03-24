<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentPostRequest;
use App\Http\Requests\createNachrichtRequest;
use App\Http\Requests\editPostRequest;
use App\Mail\AktuelleInformationen;
use App\Mail\DringendeInformationen;
use App\Mail\dringendeNachrichtStatus;
use App\Mail\newUnveroeffentlichterBeitrag;
use App\Model\Discussion;
use App\Model\Group;
use App\Model\Liste;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\Termin;
use App\Model\User;
use App\Notifications\Push;
use App\Notifications\PushNews;
use App\Repositories\GroupsRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use PDF;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Class NachrichtenController
 * @package App\Http\Controllers
 */
class NachrichtenController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(GroupsRepository $groupsRepository)
    {
        $this->groupsRepository = $groupsRepository;
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index($archiv = null)
    {
        return view('home', [
            'datum'     => Carbon::now(),
            'archiv' => $archiv,

        ]);
    }

    /**
     * Show the old News.
     *
     * @return Renderable
     */
    public function postsArchiv(Request $request)
    {
 //       $Nachrichten = Cache::remember('archiv_posts_'.auth()->id(), 60 * 5, function () {
            if (! auth()->user()->can('view all')) {
                $Nachrichten = auth()->user()->posts()->where('archiv_ab', '<', Carbon::now()->startOfDay())->where('archiv_ab', '>', auth()->user()->created_at)->orderByDesc('updated_at')->paginate(15);
                /*
                                if (auth()->user()->can('create posts')) {
                                    $eigenePosts = Post::query()->where('author', auth()->id())->whereDate('archiv_ab', '<=', Carbon::now()->startOfDay())->get();
                                    $Nachrichten = $Nachrichten->concat($eigenePosts);
                                }
                */
                //$Nachrichten = $Nachrichten->unique('id');
            } else {
                $Nachrichten = Post::where('archiv_ab', '<=', Carbon::now()->startOfDay())->withCount('users')->orderByDesc('updated_at')->paginate(15);
                //$Nachrichten = $Nachrichten->unique('id')->sortByDesc('updated_at');
            }
        /*
                    return $Nachrichten;
                });
          */
        return view('archiv.archiv', [
            'nachrichten' => $Nachrichten,
            'user' => auth()->user(),
        ]);
    }

    /**
     * @return Factory|RedirectResponse|Redirector|View
     */
    public function create(Request $request)
    {
        if (! auth()->user()->can('create posts')) {
            return redirect()->to('/home')->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $gruppen = Group::all();

        return view('nachrichten.create', [
            'gruppen' => $gruppen,
        ]);
    }

    /**
     * @param Post $posts
     * @return Factory|RedirectResponse|Redirector|View
     */
    public function edit(Request $request, Post $posts, $kiosk = '')
    {
        if (! auth()->user()->can('edit posts') and auth()->user()->id != $posts->author) {
            return redirect()->to('/home')->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $gruppen = Group::all();

        if (is_null($posts->rueckmeldung)) {
            $rueckmeldung = new Rueckmeldungen();
        } else {
            $rueckmeldung = $posts->rueckmeldung;
        }

        return view('nachrichten.edit', [
            'gruppen' => $gruppen,
            'post' => $posts,
            'rueckmeldung' => $rueckmeldung,
            'kiosk' => $kiosk,
        ]);
    }

    /**
     * @param createNachrichtRequest $request
     * @return Factory|RedirectResponse|Redirector|View
     */
    public function store(createNachrichtRequest $request)
    {
        $user = $request->user();

        if (! auth()->user()->can('create posts')) {
            return redirect()->to('/home')->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        } elseif ($request->has('urgent') and $request->input('urgent') == 1 and (! $user->can('send urgent message') or ! Hash::check($request->input('password'), $user->password))) {
            return redirect()->back()->withInput()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt für dringende Nachrichten oder Passwort ist falsch',
            ]);
        }

        $post = new Post($request->validated());

        $post->author = $user->id;
        $post->save();

        $gruppen = $request->input('gruppen');
        $gruppen = $this->groupsRepository->getGroups($gruppen);

        $post->groups()->attach($gruppen);

        if (! auth()->user()->can('release posts')) {
            $permission = Permission::query()->where('name', 'release posts')->first();

            foreach ($permission->users as $user) {
                Mail::to($user->email)->queue(new newUnveroeffentlichterBeitrag(auth()->user()->name, $post->header));
            }
        } else {
            if ($post->released) {
                $this->push($post);
            }
        }

        //Dateien verarbeiten
        if ($request->hasFile('files')) {
            if (auth()->user()->can('upload great files')) {
                try {
                    @ini_set('upload_max_filesize', '300M');
                    @ini_set('post_max_size', '300M');
                } catch (Exception $exception) {
                    redirect()->back()->with([
                        'type' => 'danger',
                        'Meldung' => $exception
                    ]);
                }
            }

            if ($request->input('collection') == 'files') {
                $post->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('files');
                    });
            } elseif ($request->input('collection') == 'header') {
                $post->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('header');
                    });
            } else {
                $post->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder
                            ->withResponsiveImages()
                            ->toMediaCollection('images');
                    });
            }
        }

        $Meldung = 'Nachricht wurde erstellt.';

        //Versenden dringender Nachrichten
        if ($request->has('urgent') and $request->input('urgent') == 1 and $user->can('send urgent message') and Hash::check($request->input('password'), $user->password)) {
            $MailGruppen = [];

            foreach ($gruppen as $gruppe) {
                $MailGruppen[] = $gruppe->name;
            }

            $users = User::whereHas('posts', function ($q) use ($MailGruppen) {
                $q->whereIn('name', $MailGruppen);
            })->get();

            $users = $users->unique('id');

            $sendTo = [];
            foreach ($users as $mailUser) {
                $header = $post->header;
                $news = $post->news;
                @Mail::to($mailUser->email)->queue(new DringendeInformationen("$header", "$news"));
                $sendTo[] = [
                    'name' => $mailUser->name,
                    'email' => $mailUser->email,
                ];
            }

            @Mail::to(auth()->user()->email)->send(new dringendeNachrichtStatus($sendTo));
            $Meldung = 'Es wurden '.count($sendTo).' Benutzer per Mail benachrichtigt.';
        }

        //Umleitung bei Rückmeldungsbedarf
        switch ($request->input('rueckmeldung')) {
            case 'email':
                return view('nachrichten.createRueckmeldung', [
                    'nachricht' => $post,
                ])->with([
                    'type' => 'success',
                    'Meldung' => $Meldung,
                ]);
                break;
            case 'poll':
                return view('nachrichten.createPoll', [
                    'nachricht' => $post,
                ])->with([
                    'type' => 'success',
                    'Meldung' => $Meldung,
                ]);
                break;
            case 'bild':
                $rueckmeldung = new Rueckmeldungen([
                    'post_id' => $post->id,
                    'type' => 'bild',
                    'empfaenger' => auth()->user()->email,
                    'ende'      => $post->archiv_ab,
                    'text'      => ' ',
                ]);
                $rueckmeldung->save();

                return redirect(url('/home#'.$post->id))->with([
                    'type' => 'success',
                    'Meldung' => 'Nachricht und Rückmeldung angelegt.',
                ]);
                break;
            case 'bild_commentable':
                    $rueckmeldung = new Rueckmeldungen([
                        'post_id'  => $post->id,
                        'type'  => 'bild',
                        'commentable'  => 1,
                        'empfaenger'  => auth()->user()->email,
                        'ende'      => $post->archiv_ab,
                        'text'      => ' ',
                    ]);
                    $rueckmeldung->save();

                    return redirect(url('/home#'.$post->id))->with([
                        'type' => 'success',
                        'Meldung' => 'Nachricht und Rückmeldung angelegt.',
                    ]);
                break;
            case 'commentable':
                    $rueckmeldung = new Rueckmeldungen([
                        'post_id'  => $post->id,
                        'type'  => 'commentable',
                        'commentable'  => 1,
                        'empfaenger'  => auth()->user()->email,
                        'ende'      => $post->archiv_ab,
                        'text'      => ' ',
                    ]);
                    $rueckmeldung->save();

                    return redirect(url('/home#'.$post->id))->with([
                        'type' => 'success',
                        'Meldung' => 'Nachricht und Rückmeldung angelegt.',
                    ]);
                break;
            default:
                return redirect(url('/home#'.$post->id))->with([
                    'type' => 'success',
                    'Meldung' => 'Nachricht angelegt.',
                ]);
                break;

        }
    }

    /**
     * @param Post $posts
     * @param editPostRequest $request
     * @return RedirectResponse|Redirector
     */
    public function update(Post $posts, editPostRequest $request, $kiosk = null)
    {
        if (! $posts->released) {
            $push = 1;
        } else {
            $push = 0;
        }

        $user = $request->user();

        if (! auth()->user()->can('edit posts') and auth()->user()->id != $posts->author) {
            return redirect()->to('/home')->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $posts->fill($request->validated());

        $posts->updated_at = $request->input('updated_at');

        if (!is_null($posts->rueckmeldung) and $posts->rueckmeldung->ende->gt($posts->archiv_ab)) {
            $posts->archiv_ab = $posts->rueckmeldung->ende;
        }
        $posts->save();

        //Gruppen

        $gruppen = $request->input('gruppen');
        $gruppen = $this->groupsRepository->getGroups($gruppen);

        $posts->groups()->detach();
        $posts->groups()->attach($gruppen);

        if ($request->hasFile('files')) {
            if (auth()->user()->can('upload great files')) {
                    @ini_set('upload_max_filesize', '300M');
                    @ini_set('post_max_size', '300M');
                    @ini_set('upload_max_size', '300M');
            }
            if ($request->input('collection') == 'files') {
                $posts->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('files');
                    });
            } elseif ($request->input('collection') == 'header') {
                $posts->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('header');
                    });
            } else {
                $posts->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('images');
                    });
            }
        }

        if ($posts->released and $push == 1) {
            $this->push($posts);
        }

        $Meldung = 'Nachricht bearbeitet';

        if ($request->has('urgent') and $request->input('urgent') == 1 and $user->can('send urgent message')) {
            if (! Hash::check($request->input('password'), $user->password)) {
                return redirect()->back()->with([
                    'type' => 'danger',
                    'Meldung' => 'Passwort falsch',
                ]);
            }

            $MailGruppen = [];

            foreach ($gruppen as $gruppe) {
                $MailGruppen[] = $gruppe->name;
            }

            $users = User::whereHas('posts', function ($q) use ($MailGruppen) {
                $q->whereIn('name', $MailGruppen);
            })->get();

            $users = $users->unique('id');

            $sendTo = [];

            foreach ($users as $mailUser) {
                $header = $posts->header;
                $news = $posts->news;
                @Mail::to($mailUser->email)->queue(new DringendeInformationen("$header", "$news"));

                $sendTo[] = [
                    'name'    => $mailUser->name,
                    'email'   => $mailUser->email,
                ];
            }

            @Mail::to(auth()->user()->email)->send(new dringendeNachrichtStatus($sendTo));
            $Meldung = 'Es wurden '.count($sendTo).' Benutzer per Mail benachrichtigt.';
        }

        if ($kiosk == 'true') {
            return redirect()->to(url('/kiosk'));
        }

        return redirect(url('/home#'.$posts->id))->with([
            'type' => 'success',
            'Meldung' => $Meldung,
        ]);
    }


    /**
     * @param null $daily
     * @param null $userSend
     * @return RedirectResponse
     */
    public function email($daily = null, $userSend = null)
    {
        if ($daily == null) {
            $daily = 'weekly';
        }

        if (is_null($userSend)) {
            $users = User::where('benachrichtigung', $daily)->whereDate('lastEmail', '<', Carbon::now())->get();
        } else {
            $users = User::where('id', $userSend)->get();
        }

        $users->load('roles');

        $countUser = 0;

        foreach ($users as $key => $user) {
            if (!$user->can('view all')) {
                $Nachrichten = $user->postsNotArchived;
            } else {
                $Nachrichten = Post::where('updated_at', '>', $user->lastEmail)->where('archiv_ab', '>', Carbon::now())->get();
            }

            $Nachrichten = $Nachrichten->filter(function ($post) use ($user) {
                if (!is_null($post->archiv_ab)) {
                    if ($post->released == 1 and $post->updated_at->greaterThanOrEqualTo($user->lastEmail) and $post->archiv_ab->greaterThan(Carbon::now())) {
                        return $post;
                    }
                }
            })->unique()->sortByDesc('updated_at')->all();

            //Elternrats-Diskussionen
            if ($user->hasRole('Elternrat')) {
                $diskussionen = Discussion::whereDate('updated_at', '>=', $user->lastEmail)->get();
            } else {
                $diskussionen = [];
            }

            //Neue Listen
            $listen = $user->listen()->where('listen.updated_at', '>=', $user->lastEmail)->where('active', 1)->get();
            $listen = $listen->unique();
            //neue Termine
            $termine = $user->termine()->where('termine.created_at', '>', $user->lastEmail)->get();
            $termine = $termine->unique();
            //@ToDo neue Dateien


            if (count($Nachrichten) > 0) {
                try {
                    Mail::to($user->email)->queue(new AktuelleInformationen($Nachrichten, $user->name, $diskussionen, $listen, $termine));
                    $user->lastEmail = Carbon::now();
                    $user->save();

                    if (!is_null($userSend)) {
                        return redirect()->back()->with([
                            'type' => 'success',
                            'Meldung' => 'Mail versandt',
                        ]);
                    }
                } catch (Exception $exception) {
                    $admin = Role::findByName('Admin');
                    $admin = $admin->users()->first();

                    Notification::send($admin, new Push('Fehler bei E-Mail', $user->email . 'konnte nicht gesendet werden'));


                }
            }
        }
    }

    public function emailDaily()
    {
        $this->email('daily');
    }

    /**
     * @param Post $posts
     * @return RedirectResponse|Redirector
     */
    public function touch(Post $posts)
    {
        if ($posts->archiv_ab->lessThan(Carbon::now()->subWeeks(3))) {
            $newPost = $posts->duplicate();
            $newPost->archiv_ab = Carbon::now()->addWeek();
            $newPost->save();
        } else {
            $posts->updated_at = Carbon::now();
            $posts->archiv_ab = Carbon::now()->addWeek();
            $posts->save();
        }

        return redirect()->to(url('/'));
    }

    /**
     * @param Post $posts
     * @return RedirectResponse|Redirector
     */
    public function release(Request $request, Post $posts)
    {
        if (! auth()->user()->can('release posts')) {
            return redirect()->to('/home')->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $posts->released = 1;
        $posts->save(['timestamps'=>false]);

        if ($posts->released) {
            $this->push($posts);
        }

        return redirect(url('/home#'.$posts->id))->with([
            'type' => 'success',
            'Meldung' => 'Nachricht veröffentlicht',
        ]);
    }

    public function archiv(Request $request, Post $posts)
    {
        if (! auth()->user()->can('edit posts')) {
            return redirect()->to('/home')->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $posts->update([
            'archiv_ab' => Carbon::now()->subDay(),
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Nachricht archiviert',
        ]);
    }

    /**
     * @param null $archiv
     * @return mixed
     */
    public function pdf(Request $request, $archiv = null)
    {
        $user = auth()->user();
        $user->with(['userRueckmeldung', 'sorgeberechtigter2', 'sorgeberechtigter2.userRueckmeldung']);
        $archivDate = Carbon::now()->endOfDay()->subWeeks(1);

        if (! $user->can('create posts')) {
            $Nachrichten = $user->posts()->with('media', 'autor', 'groups', 'rueckmeldung')->get();

            if ($archiv) {
                $Nachrichten = $Nachrichten->filter(function ($nachricht) use ($archivDate) {
                    return $nachricht->updated_at->lessThan($archivDate);
                });
            } else {
                $Nachrichten = $Nachrichten->filter(function ($nachricht) use ($archivDate) {
                    return $nachricht->updated_at->greaterThanOrEqualTo($archivDate);
                });
            }

            $Nachrichten = $Nachrichten->unique()->sortByDesc('updated_at')->paginate(30);
        } else {
            if ($archiv) {
                $Nachrichten = Post::with('media', 'autor', 'groups', 'rueckmeldung')->get();
                $Nachrichten = $Nachrichten->filter(function ($nachricht) use ($archivDate) {
                    return $nachricht->updated_at->lessThan($archivDate);
                })->sortByDesc('updated_at')->unique()->paginate(30);
            } else {
                $Nachrichten = Post::with('media', 'autor', 'groups', 'rueckmeldung')->get();
                $Nachrichten = $Nachrichten->filter(function ($nachricht) use ($archivDate) {
                    return $nachricht->updated_at->greaterThanOrEqualTo($archivDate);
                })->sortByDesc('updated_at')->unique()->paginate(30);
            }
        }

        $pdf = PDF::loadView('pdf.pdf', [
            'nachrichten' => $Nachrichten,
        ]);

        return $pdf->download(Carbon::now()->format('Y-m-d').'_Nachrichten.pdf');
    }

    /**
     * @param Post $posts
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Request $request, Post $posts)
    {
        if ($posts->author == auth()->user()->id and $posts->released == 0) {
            $posts->groups()->detach();
            if (! is_null($posts->rueckmeldung())) {
                $posts->rueckmeldung()->delete();
            }

            foreach ($posts->media as $media) {
                $media->delete();
            }

            $posts->delete();

            return response()->json([
                'message' => 'Gelöscht',
            ], 200);
        }

        return response()->json([
            'message' => 'Berechtigung fehlt',
        ], 401);
    }

    public function deleteTrashed($post)
    {
        $post = Post::onlyTrashed()->find($post);

        if (! is_null($post->rueckmeldung()->withTrashed()->first())) {
            $post->rueckmeldung()->forceDelete();
        }

        foreach ($post->media as $media) {
            $media->delete();
        }

        $post->forceDelete();

        return redirect()->back();
    }

    public function kioskView(Request $request)
    {
        if (auth()->user()->can('view all')) {
            $Nachrichten = new Collection();

            $Gruppen = Group::where('protected', 0)->with(['posts' => function ($query) {
                $query->whereDate('posts.archiv_ab', '>', Carbon::now()->startOfDay());
            }])->get();

            foreach ($Gruppen as $Gruppe) {
                $Nachrichten = $Nachrichten->concat($Gruppe->posts);
            }

            //Listen für den Kiosk
            $listen = [];
            if (auth()->user()->can('edit terminliste')) {
                $listen = Liste::query()->whereDate('ende', '>', Carbon::now())->where('active', 1)->with('eintragungen')->get();
                $listen = $listen->filter(function ($liste) {
                    $eintragungen = $liste->eintragungen()->where('termin', '>=', Carbon::now()->format('Y-m-d'))->count();
                    if ($eintragungen > 0) {
                        return $liste;
                    }
                });
            }

            return view('kiosk.index', [
                'Nachrichten'    => $Nachrichten->unique('id')->sortByDesc('updated_at'),
                'counter'       =>          0,
                'archiv'        => 0,
                'user'          => auth()->user(),
                'listen'    => $listen,
            ]);
        }

        return redirect()->back()->with([
                'type'  => 'danger',
                'Meldung'   => 'Berechtigung fehlt',
            ]);
    }

    public function storeComment(Post $posts, CommentPostRequest $request)
    {
        $posts->comment([
            'body'=> $request->comment, ],
            auth()->user()
        );

        return redirect(url('/home#'.$posts->id));
    }

    //Sendet Push-Nachricht an User
    public function push(Post $post)
    {
        $User = $post->users;
        $User = $User->unique('id');

        Notification::send($User, new PushNews($post));

        return redirect()->back();
    }

    public function stickPost(Post $post)
    {
        $post->timestamps = false;
        if ($post->sticky) {
            $post->sticky = 0;
        } else {
            $post->sticky = 1;
        }
        $post->save();

        return redirect()->back();
    }
}
