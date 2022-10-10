<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTerminRequest;
use App\Model\Group;
use App\Model\Termin;
use App\Repositories\GroupsRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class TerminController extends Controller
{
    public function __construct(GroupsRepository $groupsRepository)
    {
        $this->middleware('password_expired');
        $this->grousRepository = $groupsRepository;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
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
     * @param  Request  $request
     * @return Response
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

        Cache::forget('termine'.auth()->id());

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Termin erstellt.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Termin  $termin
     * @return Response
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
