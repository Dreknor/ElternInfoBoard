<?php

namespace App\Http\Controllers;

use App\Repositories\GroupsRepository;
use App\Http\Requests\CreateTerminRequest;
use App\Model\Groups;
use App\Model\Termin;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TerminController extends Controller
{
    public function __construct(GroupsRepository $groupsRepository)
    {
        $this->middleware('password_expired');
        $this->grousRepository = $groupsRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', Termin::class);
        return "Hallo";
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!$this->authorize('create', Termin::class)){
            return redirect(url('home'))->with([
               "type"   => "danger",
               "Meldung"    => "Berechtigung fehlt"
            ]);
        }

        return view('termine.create',[
            'gruppen'   => Groups::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateTerminRequest $request)
    {

        $this->authorize('create', Termin::class);

        $start = Carbon::parse($request->start);
        $ende = Carbon::parse($request->ende);

        if ($start->day != $ende->day){
            $start=$start->startOfDay();
            $ende=$ende->endOfDay();
        }

        $termin = new Termin([
            'terminname'    => $request->terminname,
            'start'         => $start,
            'ende'         => $ende,
            "fullDay"       => $request->fullDay
        ]);
        $termin->save();

        $gruppen= $request->input('gruppen');
        $gruppen = $this->grousRepository->getGroups($gruppen);



        $termin->groups()->attach($gruppen);


        return redirect()->back()->with([
           'type'   => 'success',
           "Meldung"    => "Termin erstellt."
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Termin  $termin
     * @return \Illuminate\Http\Response
     */
    public function show(Termin $termin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Termin  $termin
     * @return \Illuminate\Http\Response
     */
    public function edit(Termin $termin)
    {
        $this->authorize('edit', $termin);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Termin  $termin
     * @return \Illuminate\Http\Response
     */
    public function update(CreateTerminRequest $request, Termin $termin)
    {
        $this->authorize('edit', $termin);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Termin  $termin
     * @return \Illuminate\Http\Response
     */
    public function destroy(Termin $termin)
    {
        $this->authorize('delete', $termin);

        $termin->groups()->detach();
        $termin->delete();

        return redirect()->back()->with([
           'type'   => "success",
           "Meldung"    => "Termin gelöscht."
        ]);
    }
}
