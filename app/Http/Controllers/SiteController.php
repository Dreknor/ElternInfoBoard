<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSitesRequest;
use App\Model\Group;
use App\Model\Site;
use App\Repositories\GroupsRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteController extends Controller
{

    private GroupsRepository $grousRepository;

    public function __construct(GroupsRepository $groupsRepository)
    {
        $this->middleware('password_expired');
        $this->grousRepository = $groupsRepository;
    }

    public function activate(Site $site)
    {
        if (!auth()->user()->can('create sites') && $site->author_id != auth()->id()) {
            return redirect()->back()->with('danger', 'Berechtigung fehlt für diese Aktion');
        }

        $site->is_active = true;
        $site->save();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Seite aktiviert.',
        ]);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('view sites')) {
            return redirect()->back()->with('danger', 'Berechtigung fehlt für diese Aktion');
        }

        if (auth()->user()->can('create sites')) {
            $sites = Site::all();
            $gruppen = Group::all();
        } else {
            $sites = auth()->user()->sites;
            $own = Site::where('author_id', auth()->id())->get();

            $sites = $sites->merge($own);
            $gruppen = collect();
        }



        return view('sites.index',[
            'sites' => $sites,
            'gruppen' => $gruppen
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect('sites');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CreateSitesRequest $request)
    {
        try {
            $newSite = Site::create([
                'name' => $request->name,
                'author_id' => auth()->id(),
                'is_active' => false
            ]);
            $gruppen = $this->grousRepository->getGroups($request->input('gruppen'));
            $newSite->groups()->sync($gruppen);
        } catch (\Exception $e) {

            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Erstellen der Seite. ' . $e->getMessage(),
            ]);

        }

        return redirect()->route('sites.edit',[
            'site' => $newSite->id
        ])->with([
            'type' => 'success',
            'Meldung' => 'Seite erstellt.',
        ]);


    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function show(Site $site)
    {
        return view('sites.show',[
            'site' => $site
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Site  $site
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function edit(Site $site)
    {
        if (!auth()->user()->can('create sites') && $site->author_id != auth()->id()) {
            return redirect()->back()->with('danger', 'Berechtigung fehlt für diese Aktion');
        }
        Cache::delete('site' . $site->site_id);

        return view('sites.edit',[
                    'site' => $site,
                ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Site $site)
    {
        return redirect('sites');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function destroy(Site $site)
    {
        if (!auth()->user()->can('create sites') && $site->author_id != auth()->id()) {
            return redirect()->back()->with('danger', 'Berechtigung fehlt für diese Aktion');
        }

        Cache::delete('site' . $site->site_id);

        foreach ($site->blocks as $block) {
            $block->delete();
        }

        $site->delete();

        return redirect()->route('sites.index')->with([
            'type' => 'success',
            'Meldung' => 'Seite gelöscht.',
        ]);
    }
}
