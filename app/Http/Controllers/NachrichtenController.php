<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentPostRequest;
use App\Model\Liste;
use App\Notifications\PushNews;
use App\Repositories\GroupsRepository;
use App\Http\Requests\createNachrichtRequest;
use App\Http\Requests\editPostRequest;
use App\Mail\AktuelleInformationen;
use App\Mail\DringendeInformationen;
use App\Mail\dringendeNachrichtStatus;
use App\Mail\newUnveroeffentlichterBeitrag;
use App\Model\Discussion;
use App\Model\Group;
use App\Model\Post;
use App\Model\Reinigung;
use App\Model\Rueckmeldungen;
use App\Model\Termin;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

class NachrichtenController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(GroupsRepository $groupsRepository)
    {
        $this->grousRepository = $groupsRepository;
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
        $user->with(['userRueckmeldung', 'sorgeberechtigter2', 'sorgeberechtigter2.userRueckmeldung', 'sorgeberechtigter2.listen_eintragungen','termine', 'listen_eintragungen']);

        $sorg2 = $user->sorgeberechtigter2;

        $archivDate = Carbon::now()->endOfDay()->subWeeks(1);

        if (!$user->can('view all')) {
            //$Nachrichten = $user->posts()->with('media', 'autor', 'groups', 'rueckmeldung')->get();

            if ($archiv) {
                $Nachrichten = $user->posts()->whereDate('archiv_ab', '<=', Carbon::now()->startOfDay())->whereDate('archiv_ab', '>', $user->created_at)->with('media', 'autor', 'groups', 'rueckmeldung')->withCount('users')->get();

                if ($user->can('create posts')){
                    $eigenePosts = Post::query()->where('author', $user->id)->whereDate('archiv_ab', '<=', Carbon::now()->startOfDay())->get();
                    $Nachrichten = $Nachrichten->concat($eigenePosts);
                }

            } else {
                $Nachrichten = $user->posts()->whereDate('archiv_ab', '>', Carbon::now()->startOfDay())->whereDate('archiv_ab', '>', $user->created_at)->with('media', 'autor', 'groups', 'rueckmeldung')->withCount('users')->get();

                if ($user->can('create posts')){
                    $eigenePosts = Post::query()->where('author', $user->id)->whereDate('archiv_ab', '>', Carbon::now()->startOfDay())->get();
                    $Nachrichten = $Nachrichten->concat($eigenePosts);
                }
            }

            $Reinigung = Reinigung::whereIn('id', [$user->id, $user->sorg2])->whereBetween('datum', [Carbon::now()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()])->first();


        } else {

            $Reinigung = Reinigung::whereIn('users_id', [$user->id, $user->sorg2])->whereBetween('datum', [Carbon::now()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()])->first();

            if ($archiv) {
                $Nachrichten = Post::whereDate('archiv_ab', '<=', Carbon::now()->startOfDay())->with('media', 'autor', 'groups', 'rueckmeldung')->withCount('users')->get();

            } else {
                $Nachrichten = Post::whereDate('archiv_ab', '>', Carbon::now()->startOfDay())->with('media', 'autor', 'groups', 'rueckmeldung')->withCount('users')->get();
            }

        }

        $Nachrichten = $Nachrichten->unique('id')->sortByDesc('updated_at');
        $Nachrichten->load('groups.users', 'groups.users.userRueckmeldung','groups.users.sorgeberechtigter2' , 'groups.users.sorgeberechtigter2.userRueckmeldung');



        //Termine holen
        if (!$user->can('edit termin') and !$user->can('view all')) {
            $Termine = $user->termine;
        } else {
            $Termine = Termin::all();
            $Termine = $Termine->unique('id');
        }

        $Termine = $Termine->sortBy('start');


        //Termine aus Listen holen
        $listen_termine = $user->listen_eintragungen()->whereDate('termin', '>', Carbon::now()->startOfDay())->get();

        //Ergänze Listeneintragungen
        if (!is_null($listen_termine) and count($listen_termine) > 0) {
            foreach ($listen_termine as $termin) {
                $newTermin = new Termin([
                    "terminname" => $termin->liste->listenname,
                    "start" => $termin->termin,
                    "ende" => $termin->termin->copy()->addMinutes($termin->liste->duration),
                    "fullDay" => null
                ]);
                $Termine->push($newTermin);
            }
        }

        //Listentermine von Sorg2
        if (!is_null($sorg2)){
            foreach ($sorg2->listen_eintragungen()->whereDate('termin', '>', Carbon::now()->startOfDay())->get() as $termin) {
                $newTermin = new Termin([
                    "terminname" => $termin->liste->listenname,
                    "start" => $termin->termin,
                    "ende" => $termin->termin->copy()->addMinutes($termin->liste->duration),
                    "fullDay" => null
                ]);
                $Termine->push($newTermin);
            }
        }

        $Termine = $Termine->unique('id');
        $Termine = $Termine->sortBy('start');




        return view('home', [
            "nachrichten" => $Nachrichten->paginate(30),
            'datum'     => Carbon::now(),
            "archiv" => $archiv,
            "user" => $user,
            "gruppen" => Group::all(),
            "Reinigung" => $Reinigung,
            'termine' => $Termine
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function create()
    {

        if (!auth()->user()->can('create posts')) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
            ]);
        }

        $gruppen = Group::all();

        return view('nachrichten.create', [
            'gruppen' => $gruppen
        ]);
    }

    /**
     * @param Post $posts
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function edit(Post $posts, $kiosk = '')
    {
        if (!auth()->user()->can('edit posts') and auth()->user()->id != $posts->author) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
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
            "post" => $posts,
            'rueckmeldung' => $rueckmeldung,
            'kiosk' => $kiosk
        ]);
    }

    /**
     * @param createNachrichtRequest $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function store(createNachrichtRequest $request)
    {

        $user = auth()->user();

        if (!auth()->user()->can('create posts')) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
            ]);
        } elseif ($request->has('urgent') and $request->input('urgent') == 1 and (!$user->can('send urgent message') or !Hash::check($request->input('password'), $user->password))) {
            return redirect()->back()->withInput($request->all())->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt für dringende Nachrichten oder Passwort ist falsch"
            ]);
        }

        $post = new Post($request->all());


        $post->author = auth()->user()->id;
        $post->save();

        $gruppen = $request->input('gruppen');
        $gruppen = $this->grousRepository->getGroups($gruppen);

        $post->groups()->attach($gruppen);

        if (!auth()->user()->can('release posts')) {
            $permission = Permission::query()->where('name', 'release posts')->first();

            foreach ($permission->users as $user) {
                Mail::to($user->email)->queue(new newUnveroeffentlichterBeitrag(auth()->user()->name, $post->header));
            }
        } else {
            if ($post->released){
                $this->push($post);
            }
        }

        //Dateien verarbeiten
        if ($request->hasFile('files')) {
            if (auth()->user()->can('upload great files')) {
                @ini_set('upload_max_size', '300M');
                @ini_set('post_max_size', '300M');
            }

            if ($request->input('collection') == 'files') {
                $post->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('files');
                    });
            }  elseif($request->input('collection') == 'header'){
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


        $Meldung = "Nachricht wurde erstellt.";

        //Versenden dringender Nachrichten
        if ($request->has('urgent') and $request->input('urgent') == 1 and $user->can('send urgent message') and Hash::check($request->input('password'), $user->password)) {

            $MailGruppen = [];

            foreach ($gruppen as $gruppe) {
                $MailGruppen[] = $gruppe->name;
            }

            $users = User::whereHas('posts', function ($q) use ($MailGruppen) {
                $q->whereIn('name', $MailGruppen);
            })->get();


            $sendTo = [];
            foreach ($users as $mailUser) {

                $header = $post->header;
                $news = $post->news;
                @Mail::to($mailUser->email)->queue(new DringendeInformationen("$header", "$news"));
                $sendTo[] = [
                    "name" => $mailUser->name,
                    "email" => $mailUser->email
                ];
            }

            @Mail::to(auth()->user()->email)->send(new dringendeNachrichtStatus($sendTo));
            $Meldung = "Es wurden " . count($sendTo) . " Benutzer per Mail benachrichtigt.";

        }

        //Umleitung bei Rückmeldungsbedarf
        switch ($request->input('rueckmeldung')){
            case 'email':
                return view('nachrichten.createRueckmeldung', [
                    "nachricht" => $post
                ])->with([
                    "type" => "success",
                    "Meldung" => $Meldung
                ]);
                break;
            case 'bild':
                $rueckmeldung = new Rueckmeldungen([
                    'post_id'  => $post->id,
                    'type'  => 'bild',
                    'empfaenger'  => auth()->user()->email,
                    'ende'      => $post->archiv_ab,
                    'text'      => " "
                ]);
                $rueckmeldung->save();

                return redirect(url('/home#' . $post->id))->with([
                    "type" => "success",
                    "Meldung" => "Nachricht und Rückmeldung angelegt."
                ]);
                break;
            case 'bild_commentable':
                    $rueckmeldung = new Rueckmeldungen([
                        'post_id'  => $post->id,
                        'type'  => 'bild',
                        'commentable'  => 1,
                        'empfaenger'  => auth()->user()->email,
                        'ende'      => $post->archiv_ab,
                        'text'      => " "
                    ]);
                    $rueckmeldung->save();

                    return redirect(url('/home#' . $post->id))->with([
                        "type" => "success",
                        "Meldung" => "Nachricht und Rückmeldung angelegt."
                    ]);
                break;
            default:
                return redirect(url('/home#' . $post->id))->with([
                    "type" => "success",
                    "Meldung" => "Nachricht angelegt."
                ]);
                break;

        }

    }

    /**
     * @param Post $posts
     * @param editPostRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(Post $posts, editPostRequest $request, $kiosk = null)
    {

        if (!$posts->released){
            $push = 1;
        } else {
            $push = 0;
        }

        $user = auth()->user();

        if (!auth()->user()->can('edit posts') and auth()->user()->id != $posts->author) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
            ]);
        }

        $posts->fill($request->all());
        //$posts->author = auth()->user()->id;

        $posts->updated_at = $request->input('updated_at');
        $posts->save();


        //Gruppen

        $gruppen = $request->input('gruppen');
        $gruppen = $this->grousRepository->getGroups($gruppen);

        $posts->groups()->detach();
        $posts->groups()->attach($gruppen);

        if ($request->hasFile('files')) {
            if ($request->input('collection') == 'files') {
                $posts->addAllMediaFromRequest(['files'])
                    ->each(function ($fileAdder) {
                        $fileAdder->toMediaCollection('files');
                    });

            } elseif($request->input('collection') == 'header'){
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

        if ($posts->released and $push == 1){
            $this->push($posts);
        }

        $Meldung ="Nachricht bearbeitet";

        if ($request->has('urgent') and $request->input('urgent') == 1 and $user->can('send urgent message')) {
            if (!Hash::check($request->input('password'), $user->password)) {
                return redirect()->back()->with([
                    "type" => "danger",
                    "Meldung" => "Passwort falsch"
                ]);
            }

            $MailGruppen = [];

            foreach ($gruppen as $gruppe){
               $MailGruppen[] = $gruppe->name;
            }

            $users = User::whereHas('posts', function($q) use ($MailGruppen){
                $q->whereIn('name', $MailGruppen);
            })->get();


            $sendTo = [];

            foreach ($users as $mailUser) {
                $header = $posts->header;
                $news = $posts->news;
                @Mail::to($mailUser->email)->queue(new DringendeInformationen("$header", "$news"));

                $sendTo[]= [
                    "name"    => $mailUser->name,
                    "email"   => $mailUser->email
                ];


            }

            @Mail::to(auth()->user()->email)->send(new dringendeNachrichtStatus($sendTo));
            $Meldung = "Es wurden ".count($sendTo)." Benutzer per Mail benachrichtigt.";

        }

        if ($kiosk == "true"){
            return redirect(url('/kiosk'));
        }


        return redirect(url('/home#' . $posts->id))->with([
            "type" => "success",
            "Meldung" => $Meldung
        ]);

    }

    /**
     *
     */
    public function email($daily = null)
    {
        if ($daily == null){
            $daily = "weekly";
        }

        $users = User::where('benachrichtigung', $daily)->get();
        $users->load('roles');




        foreach ($users as $user) {

            if (!$user->can('view all')) {
                $Nachrichten = $user->posts;
            } else {
                $Nachrichten = Post::all();
            }


            $Nachrichten = $Nachrichten->filter(function ($post) use ($user) {
                if (!is_null($post->archiv_ab) ){
                    if ($post->released == 1 and $post->updated_at->greaterThanOrEqualTo($user->lastEmail) and $post->archiv_ab->greaterThan(Carbon::now()) ) {
                        return $post;
                    }
                }

            })->unique()->sortByDesc('updated_at')->all();


            //Elternrats-Diskussionen
            if ($user->hasRole('Elternrat')){
                $diskussionen = Discussion::all();
                $diskussionen = collect($diskussionen);
                $diskussionen = $diskussionen->filter(function ($Discussion) use ($user) {
                    if ( $Discussion->updated_at->greaterThanOrEqualTo($user->lastEmail)) {
                        return $Discussion;
                    }
                });
            } else {
                $diskussionen = [];
            }

            //@ToDo Neue
            // neue Listen
            //neue Dateien


            if (count($Nachrichten) > 0) {
                Mail::to($user->email)->queue(new AktuelleInformationen($Nachrichten, $user->name, $diskussionen));
                $user->lastEmail = Carbon::now();
                $user->save();
            }


        }
    }

    /**
     *
     */
    public function emailDaily()
    {
        $this->email('daily');
    }


    /**
     * @param Post $posts
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
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


        return redirect(url('/'));
    }

    /**
     * @param Post $posts
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function release(Post $posts)
    {
        if (!auth()->user()->can('release posts')) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
            ]);
        }

        $posts->updated_at = $posts->updated_at;
        $posts->released = 1;
        $posts->save();

        if ($posts->released){
            $this->push($posts);
        }
        return redirect(url('/home#' . $posts->id))->with([
            "type" => "success",
            "Meldung" => "Nachricht veröffentlicht"
        ]);
    }

    public function archiv (Post $posts){
        if (!auth()->user()->can('edit posts')) {
            return redirect('/home')->with([
                'type' => "danger",
                "Meldung" => "Berechtigung fehlt"
            ]);
        }

        $posts->update([
            'archiv_ab' => Carbon::now()->subDay()
        ]);
        return redirect()->back()->with([
            'type' => "success",
            "Meldung" => "Nachricht archiviert"
        ]);

    }

    /**
     * @param null $archiv
     * @return mixed
     */
    public function pdf($archiv = null)
    {
        $user = auth()->user();
        $user->with(['userRueckmeldung', 'sorgeberechtigter2', 'sorgeberechtigter2.userRueckmeldung']);
        $archivDate = Carbon::now()->endOfDay()->subWeeks(1);

        if (!$user->can('create posts')) {
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

        $pdf = \PDF::loadView('pdf.pdf', [
            "nachrichten" => $Nachrichten
        ]);
        return $pdf->download(\Carbon\Carbon::now()->format('Y-m-d') . '_Nachrichten.pdf');

    }

    /**
     * @param Post $posts
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Post $posts)
    {
        if ($posts->author == auth()->user()->id) {

            $posts->groups()->detach();
            if (!is_null($posts->rueckmeldung())) {
                $posts->rueckmeldung()->delete();
            }
            $posts->delete();

            return response()->json([
                "message" => "Gelöscht"
            ], 200);
        }

        return response()->json([
            "message" => "Berechtigung fehlt"
        ], 401);
    }

    /**
     *
     */
    public function kioskView(){

        if (auth()->user()->can('view all')){
            $Nachrichten = new Collection();


            $Gruppen = Group::where('protected', 0)->with(['posts' => function ($query){
                $query->whereDate('posts.archiv_ab', '>', Carbon::now()->startOfDay());
            }])->get();

            foreach ($Gruppen as $Gruppe){
                $Nachrichten = $Nachrichten->concat($Gruppe->posts);
            }


            //Listen für den Kiosk
            $listen = [];
            if (auth()->user()->can('edit terminliste')){
                $listen = Liste::query()->whereDate('ende', '>', Carbon::now())->with('eintragungen')->get();
            }


            return view('kiosk.index', [
                "Nachrichten"    => $Nachrichten->unique('id')->sortByDesc('updated_at'),
                "counter"       =>          0,
                'archiv'        => 0,
                'user'          => auth()->user(),
                'listen'    => $listen
            ]);
        }


        return redirect()->back()->with([
                'type'  => "danger",
                'Meldung'   => "Berechtigung fehlt"
            ]);
    }

    public function storeComment(Post $posts, CommentPostRequest $request){

        $posts->comment([
            'body'=> $request->comment],
            auth()->user()
        );

        return redirect(url('/home#'.$posts->id));
    }

    //Sendet Push-Nachricht an User
    public function push(Post $post){

        $User = $post->users;
        $User = $User->unique('id');

        Notification::send($User,new PushNews($post));
        return redirect()->back();
    }
}
