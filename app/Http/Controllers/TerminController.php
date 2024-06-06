<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTerminRequest;
use App\Model\Group;
use App\Model\Termin;
use App\Repositories\GroupsRepository;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

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

        $termin->update($request->validated());
        $termin->groups()->sync($request->input('gruppen'));

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

        $start = Carbon::parse($request->start);
        $ende = Carbon::parse($request->ende);

        if ($start->day != $ende->day) {
            $start = $start->startOfDay();
            $ende = $ende->endOfDay();
        }

        $termin = new Termin([
            'terminname' => $request->terminname,
            'start' => $start,
            'ende' => $ende,
            'fullDay' => $request->fullDay,
        ]);
        $termin->save();

        $gruppen = $request->input('gruppen');
        $gruppen = $this->grousRepository->getGroups($gruppen);

        $termin->groups()->attach($gruppen);

        $termin->notify(
            users: $termin->users,
            message: 'Neuer Termin: ' . $termin->terminname,
            title: 'Neuer Termin',
            type: 'Termine');

        Cache::forget('termine'.auth()->id());

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
