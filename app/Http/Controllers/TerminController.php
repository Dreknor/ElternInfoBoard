<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTerminRequest;
use App\Model\Group;
use App\Model\Post;
use App\Model\Termin;
use App\Repositories\GroupsRepository;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TerminController extends Controller
{
    private GroupsRepository $grousRepository;

    public function __construct(GroupsRepository $groupsRepository)
    {
        $this->middleware('password_expired');
        $this->grousRepository = $groupsRepository;
    }


    public function edit(Termin $termin)
    {
        return view('termine.edit', [
            'gruppen' => Group::all(),
            'termin' => $termin,
        ]);
    }

    public function update(CreateTerminRequest $request, Termin $termin)
    {
        if (!auth()->user()->can('edit termin')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        try {
            $start = Carbon::parse($request->start);
            $ende = Carbon::parse($request->ende);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Erstellen des Termins. Ungültiges Datumsformat. Bitte verwenden Sie das Format: dd.mm.yyyy hh:mm:ss',
            ]);
        }

        try {
            $termin->update(
                [
                    'terminname' => $request->terminname,
                    'start' => $start,
                    'ende' => $ende,
                    'fullDay' => $request->fullDay,
                    'public' => $request->public,
                ]
            );
        } catch (\Exception $e) {

            Log::error('Termin: Fehler beim Aktualisieren des Termins: ' . $e->getMessage());

            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Aktualisieren des Termins.',
            ]);
        }

        try {
            $gruppen = $this->grousRepository->getGroups($request->input('gruppen'));
            $termin->groups()->sync($gruppen);

        } catch (\Exception $e) {

            Log::error('Termin: Fehler beim Aktualisieren der Gruppen: ' . $e->getMessage());

            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Aktualisieren der Gruppen.',
            ]);
        }


        Cache::forget('termine' . auth()->id());

        return redirect(url('/'))->with([
            'type' => 'success',
            'Meldung' => 'Termin aktualisiert.',
        ]);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return View|RedirectResponse
     * @throws AuthorizationException
     */
    public function create()
    {
        if (! $this->authorize('create', Termin::class)) {
            return redirect()->to(url('home'))->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        return view('termine.create', [
            'gruppen' => Group::all(),
        ]);
    }

    public function createFromPost(Post $post)
    {
        if (! $this->authorize('create', Termin::class)) {
            return redirect()->to(url('home'))->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung Termine zu erstellen fehlt',
            ]);
        }

        $pattern = '^[0-3]?[0-9].[0-3]?[0-9].(?:[0-9]{2})?[0-9]{2}$^';
        $matches = [];
        $termin = preg_match($pattern, $post->header, $matches);

        if (!$termin) {
            $termin = preg_match($pattern, $post->news, $matches);
        }

        if (!$termin) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Kein Datum im Beitrag gefunden.',
            ]);
        }

        $terminname = $post->header;
        $terminname = str_replace($matches[0], '', $terminname);

        $start = Carbon::parse($matches[0]);
        $ende = Carbon::parse($matches[0]);


        $termin = new Termin([
            'terminname' => $terminname,
            'start' => $start,
            'ende' => $ende,
            'fullDay' => false,
            'public' => false,
        ]);






        return view('termine.createFromPost', [
            'gruppen' => Group::all(),
            'termin' => $termin,
            'post' => $post
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param CreateTerminRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(CreateTerminRequest $request)
    {
        $this->authorize('create', Termin::class);

        try {
            $start = Carbon::parse($request->start);
            $ende = Carbon::parse($request->ende);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Erstellen des Termins. Ungültiges Datumsformat. Bitte verwenden Sie das Format: dd.mm.yyyy hh:mm:ss',
            ]);
        }

        if ($start->day != $ende->day) {
            $start = $start->startOfDay();
            $ende = $ende->endOfDay();
        }

        try {
            $termin = new Termin([
                'terminname' => $request->terminname,
                'start' => $start,
                'ende' => $ende,
                'fullDay' => $request->fullDay,
                'public' => $request->public,
            ]);
            $termin->save();

            $gruppen = $request->input('gruppen');
            $gruppen = $this->grousRepository->getGroups($gruppen);

            $termin->groups()->attach($gruppen);

            $termin->notify(
                users: $termin->users,
                title: 'Neuer Termin',
                message: 'Neuer Termin: ' . $termin->terminname,
                type: 'Termine');

            Cache::forget('termine'.auth()->id());
        } catch (\Exception $e) {
            Log::error('Termin: Fehler beim Erstellen des Termins: ' . $e->getMessage());
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Fehler beim Erstellen des Termins.',
            ]);
        }


        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Termin erstellt.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Termin $termin
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Termin $termin)
    {
        $this->authorize('delete', $termin);

        $termin->groups()->detach();
        $termin->delete();

        Cache::forget('termine'.auth()->id());

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Termin gelöscht.',
        ]);
    }
}
