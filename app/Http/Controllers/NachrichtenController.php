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
use App\Model\Notification;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\Settings;
use App\Model\User;
use App\Repositories\GroupsRepository;
use App\Repositories\WordpressRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use PDF;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class NachrichtenController
 */
class NachrichtenController extends Controller
{
    private GroupsRepository $groupsRepository;

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
            'datum' => Carbon::now(),
            'archiv' => $archiv,

        ]);
    }

    /**
     * Show the old News.
     *
     * @return Renderable
     */
    public function postsArchiv($month = null)
    {
        $first_post = (auth()->user()->can('view all')) ? Post::first() : auth()->user()->posts()->orderBy('updated_at')->first();

        if ($month == null) {
            $month = Carbon::now();
        } else {
            $month = Carbon::parse($month);
        }

        if (! auth()->user()->can('view all')) {
            $Nachrichten = auth()->user()->posts()
                ->where('archiv_ab', '<', ($month->copy()->endOfMonth()->greaterThan(Carbon::now())) ? Carbon::now() : $month->copy()->endOfMonth())
                ->where('archiv_ab', '>', $month->copy()->startOfMonth())
                ->where('archiv_ab', '>', auth()->user()->created_at)
                ->orderByDesc('updated_at')->paginate(15);
        } else {
            $Nachrichten = Post::query()
                ->where('archiv_ab', '<=', ($month->copy()->endOfMonth()->greaterThan(Carbon::now())) ? Carbon::now() : $month->copy()->endOfMonth())
                ->where('archiv_ab', '>', $month->copy()->startOfMonth())
                ->orderByDesc('updated_at')
                ->paginate(15);
        }

        return view('archiv.archiv', [
            'nachrichten' => $Nachrichten,
            'user' => auth()->user(),
            'first_post' => $first_post
        ]);
    }

    public function postsExternal()
    {
        if (!auth()->user()->can('view external offer') or Settings::firstWhere(['setting' => 'externe Angebote'])->options['active'] !=1){
            return redirect()->back()->with([
               'type' => 'warning',
               'Medldung' => 'Aufruf nicht möglich'
            ]);
        }

        $nachrichten = Cache::remember('posts_external_'.auth()->id(), 1, function () {
            $user = auth()->user();

            if (! $user->can('view all')) {
                $Nachrichten = $user->postsNotArchived()
                    ->distinct()
                    ->where('external', 1)
                    ->orderByDesc('updated_at')
                    ->get();

                if ($user->can('create posts')) {
                    $eigenePosts = Post::query()
                        ->where('author', $user->id)
                        ->whereDate('archiv_ab', '>', Carbon::now()->startOfDay())
                        ->where('external', 1)
                        ->get();
                    $Nachrichten = $Nachrichten->concat($eigenePosts);
                }
            } else {
                $Nachrichten = Post::whereDate('archiv_ab', '>', Carbon::now()->startOfDay())
                    ->where('external', 1)
                    ->orderByDesc('updated_at')
                    ->get();
            }

            $Nachrichten = $Nachrichten->unique('id');


            return $Nachrichten->paginate(30);
        });

        return view('externalPost.index', [
            'nachrichten' => $nachrichten,
            'user' => auth()->user(),
        ]);
    }

    /**
     * @return View|RedirectResponse
     */
    public function create()
    {
        if (! auth()->user()->can('create posts')) {
            return redirect()->to('/home')->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $gruppen = Group::all();
        $external = Cache::remember('external_offers', 120, function (){
           return Settings::firstWhere(['setting' => 'externe Angebote'])->options['active'];
        });

        $wp_push = Cache::remember('wp_push_'.auth()->id(), 120, function (){
            if (Settings::firstWhere(['setting' => 'Push to WordPress'])->options['active'] == 1 and auth()->user()->can('push to wordpress')){
                return true;
            }
           return false;
        });


        return view('nachrichten.create', [
            'gruppen' => $gruppen,
            'external' => $external,
            'wp_push' =>$wp_push
        ]);
    }

    /**
     * @param  Post  $posts
     * @return RedirectResponse|View
     */
    public function edit(Post $posts): View|RedirectResponse
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

        $external = Cache::remember('external_offers', 120, function (){
            return Settings::firstWhere(['setting' => 'externe Angebote'])->options['active'];
        });
        $wp_push = Cache::remember('wp_push_'.auth()->id(), 120, function (){
            if (Settings::firstWhere(['setting' => 'Push to WordPress'])->options['active'] == 1 and auth()->user()->can('push to wordpress')){
                return true;
            }
            return false;
        });

        return view('nachrichten.edit', [
            'gruppen' => $gruppen,
            'post' => $posts,
            'rueckmeldung' => $rueckmeldung,
            'kiosk' => null,
            'external' => $external,
            'wp_push' => $wp_push
        ]);
    }

    /**
     * @param createNachrichtRequest $request
     * @return Factory|RedirectResponse|Redirector|View
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
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
        ($post->news == '') ? $post->news = $post->header : null;


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
                        'Meldung' => $exception,
                    ]);
                }
            }

            if ($request->input('collection') == 'files') {
                $post->addAllMediaFromRequest()
                    ->each(fn($fileAdder) => $fileAdder->toMediaCollection('files'));
            } elseif ($request->input('collection') == 'header') {
                $post->addAllMediaFromRequest()
                    ->each(fn($fileAdder) => $fileAdder->toMediaCollection('header'));
            } else {
                $post->addAllMediaFromRequest()
                    ->each(fn($fileAdder) => $fileAdder
                        ->withResponsiveImages()
                        ->toMediaCollection('images'));
            }
        }

        if ($request->wp_push){
            $repository = new WordpressRepository();
            $repository->should_post($post);
        }

        $Meldung = 'Nachricht wurde erstellt.';

        //Versenden dringender Nachrichten
        if ($request->has('urgent') and $request->input('urgent') == 1 and $user->can('send urgent message') and Hash::check($request->input('password'), $user->password)) {
            $sendTo = $this->sendMailToGroupsUsers($gruppen, $post);

            @Mail::to(auth()->user()->email)->queue(new dringendeNachrichtStatus($sendTo));
            $Meldung = 'Es wurden ' . count($sendTo) . ' Benutzer per Mail benachrichtigt.';
            $post->update([
                'send_at' => Carbon::now()
            ]);
        }

        //Umleitung bei Rückmeldungsbedarf
        switch ($request->input('rueckmeldung')) {
            case 'abfrage':
                return redirect(url('rueckmeldung/create/'.$post->id.'/abfrage'));
                break;
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
                    'ende' => $post->archiv_ab,
                    'text' => ' ',
                ]);
                $rueckmeldung->save();

                return redirect(url('/home#'.$post->id))->with([
                    'type' => 'success',
                    'Meldung' => 'Nachricht und Rückmeldung angelegt.',
                ]);
                break;
            case 'bild_commentable':
                    $rueckmeldung = new Rueckmeldungen([
                        'post_id' => $post->id,
                        'type' => 'bild',
                        'commentable' => 1,
                        'empfaenger' => auth()->user()->email,
                        'ende' => $post->archiv_ab,
                        'text' => ' ',
                    ]);
                    $rueckmeldung->save();

                    return redirect(url('/home#'.$post->id))->with([
                        'type' => 'success',
                        'Meldung' => 'Nachricht und Rückmeldung angelegt.',
                    ]);
                break;
            case 'commentable':
                    $rueckmeldung = new Rueckmeldungen([
                        'post_id' => $post->id,
                        'type' => 'commentable',
                        'commentable' => 1,
                        'empfaenger' => auth()->user()->email,
                        'ende' => $post->archiv_ab,
                        'text' => ' ',
                    ]);
                    $rueckmeldung->save();

                    return redirect(url('/home#'.$post->id))->with([
                        'type' => 'success',
                        'Meldung' => 'Nachricht und Rückmeldung angelegt.',
                    ]);
                break;
            default:


                    $pattern = '^[0-3]?[0-9].[0-3]?[0-9].(?:[0-9]{2})?[0-9]{2}$^';



                    if (preg_match($pattern, $post->header) or preg_match($pattern, $post->news)){
                       return redirect(url('termine/create/'.$post->id))->with([
                           'type' => 'success',
                           'Meldung' => 'Die Nachricht wurde erstellt. Es wurde im Text ein Datum gefunden. Soll dieses als Termin angelegt werden?',
                       ]);
                    }

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
     * @param null $kiosk
     * @return RedirectResponse|Redirector
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
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

        if (! is_null($posts->rueckmeldung) and $posts->rueckmeldung->ende->gt($posts->archiv_ab)) {
            $posts->archiv_ab = $posts->rueckmeldung->ende;
        }

        ($posts->news == '') ? $posts->news = $posts->header : null;

        $posts->save();


        if ($request->wp_push){
            $repository = new WordpressRepository();
            $repository->should_post($posts);
        }


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
                $posts->addAllMediaFromRequest()
                    ->each(fn($fileAdder) => $fileAdder->toMediaCollection('files'));
            } elseif ($request->input('collection') == 'header') {
                $posts->addAllMediaFromRequest()
                    ->each(fn($fileAdder) => $fileAdder->toMediaCollection('header'));
            } else {
                $posts->addAllMediaFromRequest()
                    ->each(fn($fileAdder) => $fileAdder
                        ->withResponsiveImages()
                        ->toMediaCollection('images'));
            }


            /*
                        $files = $request->files->all();
                        foreach ($files['files'] as $file) {
                            if (substr($file->getMimeType(), 0, 5) == 'image')
                                $collection = 'images';
                            if ($request->input('collection') == 'header') {
                                $collection = 'header';
                            } else {
                                $collection = 'files';
                            }

                            $posts
                                ->addMedia($file)
                                ->toMediaCollection($collection);
                        }
            */
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

            $sendTo = $this->sendMailToGroupsUsers($gruppen, $posts);

            @Mail::to(auth()->user()->email)->send(new dringendeNachrichtStatus($sendTo));
            $Meldung = 'Es wurden ' . count($sendTo) . ' Benutzer per Mail benachrichtigt.';
            $posts->update([
                'send_at' => Carbon::now()
            ]);
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
     * @param  null  $daily
     * @param  null  $userSend
     * @return RedirectResponse|void
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


        foreach ($users as $user) {
            if (! $user->can('view all')) {
                $Nachrichten = $user->postsNotArchived;
            } else {
                $Nachrichten = Post::query()
                    ->where('updated_at', '>', (!is_null($user->lastEmail)? $user->lastEmail : $user->created_at))
                    ->where('archiv_ab', '>', Carbon::now())
                    ->get();
            }

            $Nachrichten = $Nachrichten->filter(function ($post) use ($user) {
                    if (! is_null($post->archiv_ab) and $post->released == 1 and $post->updated_at->greaterThanOrEqualTo($user->lastEmail) and $post->archiv_ab->greaterThan(Carbon::now())) {
                        return $post;
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

                    if (! is_null($userSend)) {
                        return redirect()->back()->with([
                            'type' => 'success',
                            'Meldung' => 'Mail versandt',
                        ]);
                    }
                } catch (Exception $exception) {
                    $admin = Role::findByName('Administrator');
                    $admin = $admin->users()->first();

                    $admin->notify(new Push('Fehler beim Mailversand', $exception->getMessage()));

                }
            }
        }
    }

    /**
     * @return void
     */
    public function emailDaily()
    {
        $this->email('daily');
    }

    /**
     * @param  Post  $posts
     * @return RedirectResponse
     */
    public function touch(Post $posts)
    {
        if ($posts->archiv_ab->lessThan(Carbon::now()->subWeeks(3))) {
            $newPost = $posts->duplicate();
            $newPost->archiv_ab = Carbon::now()->addWeeks(2);
            $newPost->updated_at = Carbon::now();
            $newPost->released = 0;
            $newPost->send_at = null;
            $newPost->author = auth()->id();
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
    public function release(Post $posts)
    {
        if (! auth()->user()->can('release posts')) {
            return redirect()->to('/home')->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $posts->released = 1;
        $posts->save(['timestamps' => false]);

        if ($posts->released) {
            $this->push($posts);
        }



        return redirect(url('/home#'.$posts->id))->with([
            'type' => 'success',
            'Meldung' => 'Nachricht veröffentlicht',
        ]);
    }

    /**
     * @param Post $posts
     * @return RedirectResponse
     */
    public function archiv(Post $posts)
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
     * @param  null  $archiv
     * @return mixed
     */
    public function pdf($archiv = null)
    {
        $user = auth()->user();
        $user->with(['userRueckmeldung', 'sorgeberechtigter2', 'sorgeberechtigter2.userRueckmeldung']);
        $archivDate = Carbon::now()->endOfDay()->subWeeks();

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
            $Nachrichten = Post::with('media', 'autor', 'groups', 'rueckmeldung')->get();
            if ($archiv) {
                $Nachrichten = $Nachrichten->filter(fn($nachricht) => $nachricht->updated_at->lessThan($archivDate))->sortByDesc('updated_at')->unique()->paginate(30);
            } else {
                $Nachrichten = $Nachrichten->filter(fn($nachricht) => $nachricht->updated_at->greaterThanOrEqualTo($archivDate))->sortByDesc('updated_at')->unique()->paginate(30);
            }
        }

        $pdf = PDF::loadView('pdf.pdf', [
            'nachrichten' => $Nachrichten,
        ]);

        return $pdf->download(Carbon::now()->format('Y-m-d').'_Nachrichten.pdf');
    }

    /**
     * @param Post $posts
     * @return RedirectResponse
     *
     */
    public function destroy(Post $post)
    {

        if ($post->author == auth()->id() or auth()->user()->can('delete posts')) {
            $post->groups()->detach();
            if (!is_null($post->rueckmeldung())) {
                $post->rueckmeldung()->delete();
            }

            foreach ($post->media as $media) {
                $media->delete();
            }

            $post->delete();

            return redirect()->to('/home')->with([
                'type' => 'success',
                'Meldung' => 'Nachricht gelöscht',
            ]);

        }

        return redirect()->to('/home')->with([
            'type' => 'danger',
            'Meldung' => 'Berechtigung fehlt',
        ]);
    }

    /**
     * @param $post
     * @return RedirectResponse
     */
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


    /**
     * @param Post $posts
     * @param CommentPostRequest $request
     * @return Application|RedirectResponse|Redirector
     */
    public function storeComment(Post $posts, CommentPostRequest $request)
    {
        $posts->comment([
            'body' => $request->comment, ],
            auth()->user()
        );

        return redirect(url('/home#'.$posts->id));
    }

    //Sendet Push-Nachricht an User

    /**
     * @param Post $post
     * @return RedirectResponse
     */
    public function push(Post $post)
    {
        $User = $post->users;
        $User = $User->unique('id');

        if ($post->external) {
            $header = 'Neues externes Angebot';
        } else {
            $header = 'Neue Nachricht';
        }


        $media = $post->getMedia('header')->first();

        if (!is_null($media)){

            $icon = url('/image/'.$post->getMedia('header')->first()->id);
        } else {
            $icon = (config('app.favicon')) ? url('img/'.config('app.favicon')) : '';
        }

        $notifications= [];

        foreach ($User as $user) {
            $notifications[] = [
                'user_id' => $user->id,
                'title' => $header,
                'message' => $post->header,
                'url' => ($post->external) ? url('/external#'.$post->id) : url('/home#'.$post->id),
                'icon' => $icon,
                'type' => ($post->external) ? 'Ex. Angebot' : 'Nachrichten',
                'created_at' => Carbon::now(),
            ];
        }

        Notification::insert($notifications);

        return redirect()->back();
    }

    /**
     * @param Post $post
     * @return RedirectResponse
     */
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

    /**
     * @param array $gruppen
     * @param Post $post
     * @return array
     */
    public function sendMailToGroupsUsers(Collection|array $gruppen, Post $post): array
    {
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
        return $sendTo;
    }

    public function findPost(Post $post)
    {
        if ($post->archiv_ab->lte(Carbon::now())) {
            return redirect(url('/archiv#' . $post->id));
        }

        return redirect(url('/#' . $post->id));
    }
}
