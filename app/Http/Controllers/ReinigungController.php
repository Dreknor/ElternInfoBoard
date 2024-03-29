<?php

namespace App\Http\Controllers;

use App\Exports\ReinigungExport;
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
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReinigungController extends Controller
{
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
        $ende = Carbon::createFromFormat('d.m', '30.8');

        if ($datum->month > 6) {
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
            $Bereiche = Group::query()->whereNotNull('bereich')->where('bereich', '!=', 'Aufnahme')->pluck('bereich')->unique();
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
