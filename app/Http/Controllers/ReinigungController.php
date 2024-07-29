<?php

namespace App\Http\Controllers;

use App\Exports\ReinigungExport;
use App\Http\Requests\CreateAutoReinigungRequest;
use App\Http\Requests\ReinigungsRequest;
use App\Model\Group;
use App\Model\Reinigung;
use App\Model\ReinigungsTask;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReinigungController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function autoCreateStart($bereich)
    {
        $task = ReinigungsTask::all();

        $bereich = Group::where('bereich', $bereich)->get();

        if (!auth()->user()->can('edit reinigung')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        if ($bereich->count() < 1) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Bereich enthält keine Gruppen',
            ]);
        }

        return view('reinigung.autoCreate', [
            'bereich' => $bereich,
            'aufgaben' => $task,
            'roles' => Role::all(),
        ]);
    }


    public function autoCreate(CreateAutoReinigungRequest $request, $bereich)
    {

        if (!auth()->user()->can('edit reinigung')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $start = Carbon::createFromFormat('Y-m-d', $request->start)->startOfWeek();
        $ende = Carbon::createFromFormat('Y-m-d', $request->end)->endOfWeek();


        if (!is_null($request->exclude) and count($request->exclude) > 0 and $request->exclude[0] != 0) {
            $excludeGroups = $request->exclude;
        } else {
            $excludeGroups = [];
        }
        $users = User::query()->whereHas('groups', function ($query) use ($excludeGroups, $bereich) {
            $query->where('bereich', '=', $bereich)->whereNotIn('groups.id', $excludeGroups);
        })->whereHas('reinigung', function ($query) use ($start, $ende, $bereich) {
            $query->whereBetween('datum', [$start, $ende])
                ->where('bereich', '=', $bereich);
        }, '<', 1)->get();


        $users_all = $users->shuffle();
        Log::info('Nutzer:' . $users_all->count());
        $users_all = $users_all->unique('id');
        Log::info('Nutzer unique:' . $users_all->count());


        $tasks = ReinigungsTask::whereIn('id', $request->aufgaben)->get();
        $date = $start->copy();


        while ($date->lte($ende)) {
            if ($users_all->count() > 0) {
                foreach ($tasks as $task) {
                    $user = $users_all->shift();
                    if (!is_null($user)) {
                        $reinigung = new Reinigung();
                        $reinigung->bereich = $bereich;
                        $reinigung->datum = $date;
                        $reinigung->users_id = $user->id;
                        $reinigung->aufgabe = $task->task;
                        $reinigung->save();

                        //Sorgeberechtigter 2 entfernen
                        if ($user->sorg2 != null) {
                            $key = $users_all->search(function ($item) use ($user) {
                                return $item->id == $user->sorg2;
                            });

                            if ($key !== false) {
                                $users_all->forget($key);
                            }
                        }


                    }

                    $forget = $users_all->firstWhere('id', $user->id);
                    if ($forget) {
                        $users_all->forget($forget);
                    }
                }

            } else {
                return redirect()->back()->with([
                    'type' => 'danger',
                    'Meldung' => 'Nicht genügend Nutzer für die Aufgaben vorhanden',
                ]);
            }

            $date->addWeek();

        }


                return redirect()->to(url('reinigung'))->with([
                    'type' => 'success',
                    'Meldung' => 'Plan aktualisiert',
                ]);
    }

    /**
     * @param $bereich
     * @return RedirectResponse|BinaryFileResponse
     */
    public function export($bereich)
    {
        if (auth()->user()->can('edit reinigung')) {
            return Excel::download(new ReinigungExport($bereich), Carbon::now()->format('Y-m-d').'_'.$bereich.'_Reinigung.xlsx');
        }

        return redirect()->back()->with([
            'type' => 'danger',
            'Meldung' => 'Berechtigung fehlt',
        ]);
    }

    /**
     * @param $Bereich
     * @param Reinigung $reinigung
     * @return RedirectResponse|void
     */
    public function destroy($Bereich, Reinigung $reinigung)
    {
        if (auth()->user()->can('edit reinigung') and $reinigung->bereich == $Bereich) {
            $reinigung->delete();

            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Reinigungsaufgabe wurde gelöscht.',
            ]);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $datum = Carbon::now()->startOfWeek()->startOfDay();

        if ($datum->month < 6) {
            $ende = Carbon::createFromFormat('d.m', '30.8');
        } else {
            $ende = Carbon::createFromFormat('d.m', '30.8');
            $ende->addYear();
        }


        if (! $user->can('edit reinigung') and ! $user->can('view reinigung')) {
            $user->load('groups');
            $Bereiche = $user->groups->pluck('bereich')->unique();
            $Bereiche = $Bereiche->filter(function ($value) {
                if ($value != 'Aufnahme') {
                    return $value;
                }
            });
        } else {
            $Bereiche = Group::query()
                ->whereNotNull('bereich')
                ->where('bereich', '!=', 'Aufnahme')
                ->pluck('bereich')
                ->unique();
        }

        $Reinigung = [];

        foreach ($Bereiche as $Bereich) {
            $Reinigung[$Bereich] = Reinigung::query()
                ->where('bereich', $Bereich)
                ->whereDate('datum', '>=', $datum)
                ->orderBy('datum')
                ->get();
        }

        return view('reinigung.show', [
            'Bereiche' => $Bereiche,
            'Familien' => $Reinigung,
            'datum' => $datum,
            'user' => $user,
            'ende' => $ende,
            'aufgaben' => ReinigungsTask::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|\Illuminate\Contracts\View\View|RedirectResponse
     */
    public function create(Request $request, $Bereich, $Datum)
    {
        if (! $request->user()->can('edit reinigung')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }

        $datum = Carbon::createFromFormat('Ymd', $Datum)->startOfWeek()->startOfDay();
        $ende = $datum->copy()->endOfWeek()->endOfDay();

        $newusers = User::whereHas('groups', function ($query) use ($Bereich) {
            $query->where('bereich', '=', $Bereich);
        })->get();

        $newusers = $newusers->sortBy('familie_name');


        $Reinigung = Reinigung::query()
                ->where('bereich', $Bereich)
                ->whereDate('datum', '>=', $datum->copy()->subWeek())
                ->orderBy('datum')
                ->get();

        $Aufgaben = ReinigungsTask::all();

        return view('reinigung.edit', [
            'Bereich' => $Bereich,
            'Familien' => $Reinigung,
            'datum' => $datum,
            'ende' => $ende,
            'users' => $newusers,
            'aufgaben' => $Aufgaben,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param $Bereich
     * @param  ReinigungsRequest  $request
     * @return RedirectResponse
     */
    public function store($Bereich, ReinigungsRequest $request)
    {
        $task = ReinigungsTask::find($request->aufgabe);
        $reinigung = new Reinigung($request->validated());
        $reinigung->bereich = $Bereich;
        $reinigung->aufgabe = $task->task;
        $reinigung->save();

        return redirect()->to(url('reinigung'))->with([
            'type' => 'success',
            'Meldung' => 'Plan aktualisiert',
        ]);
    }
}
