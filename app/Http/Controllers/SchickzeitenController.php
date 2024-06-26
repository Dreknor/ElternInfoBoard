<?php

namespace App\Http\Controllers;

use App\Exports\SchickzeitenExport;
use App\Http\Requests\CreateChildRequest;
use App\Http\Requests\SchickzeitRequest;
use App\Mail\SchickzeitenReminder;
use App\Model\Schickzeiten;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class SchickzeitenController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $zeiten = auth()->user()->schickzeiten;
        $childs = $zeiten->pluck('child_name')->unique();

        $weekdays = [
            '1' => 'Montag',
            '2' => 'Dienstag',
            '3' => 'Mittwoch',
            '4' => 'Donnerstag',
            '5' => 'Freitag',
        ];

        return view('schickzeiten.index', [
            'schickzeiten' => $zeiten,
            'childs' => $childs,
            'weekdays' => $weekdays,
        ]);
    }

    /**
     * @return Application|Factory|View
     */
    public function indexVerwaltung()
    {
        $zeiten = Schickzeiten::all();
        $childs = $zeiten->unique(fn($item) => $item['users_id'].$item['child_name']);

        $parents = User::whereHas('groups', function (Builder $query) {
            $query->where('bereich', 'Grundschule');
        })->get();
        $parents = $parents->sortBy('Familiename');

        $weekdays = [
            '1' => 'Montag',
            '2' => 'Dienstag',
            '3' => 'Mittwoch',
            '4' => 'Donnerstag',
            '5' => 'Freitag',
        ];

        return view('schickzeiten.index_verwaltung', [
            'schickzeiten' => $zeiten,
            'childs' => $childs,
            'weekdays' => $weekdays,
            'parents' => $parents,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return RedirectResponse
     */
    public function createChild(CreateChildRequest $request)
    {
       Schickzeiten::firstOrCreate([
            'users_id' => auth()->id(),
            'child_name' => $request->child,
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Kind angelegt',
        ]);
    }

    /**
     * @param CreateChildRequest $request
     * @return RedirectResponse
     */
    public function createChildVerwaltung(CreateChildRequest $request)
    {
        Schickzeiten::firstOrCreate([
            'users_id' => $request->parent,
            'child_name' => $request->child,
        ], [
            'changedBy' => Auth::id(),
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Kind angelegt',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param $parent
     * @param SchickzeitRequest $request
     * @return RedirectResponse
     */
    public function storeVerwaltung($parent, SchickzeitRequest $request)
    {
        $weekdays = [
            'Montag' => '1',
            'Dienstag' => '2',
            'Mittwoch' => '3',
            'Donnerstag' => '4',
            'Freitag' => '5',
        ];

       Schickzeiten::query()->where([
            'child_name' => $request->child,
            'weekday' => $weekdays[$request->weekday],
            'users_id' => $parent,
        ])->update([
            'changedBy' => Auth::id(),
            'deleted_at' => Carbon::now(),
        ]);

        $neueSchickzeit = new Schickzeiten([
            'users_id' => $parent,
            'child_name' => $request->child,
            'weekday' => $weekdays[$request->weekday],
            'type' => $request->type,
            'time' => $request->time,
            'changedBy' => Auth::id(),
        ]);

        $neueSchickzeit->save();

        if ($request->type == 'ab' and $request->time_spaet != '') {
            $neueSchickzeit2 = new Schickzeiten([
                'users_id' => $parent,
                'child_name' => $request->child,
                'weekday' => $weekdays[$request->weekday],
                'type' => 'spät.',
                'time' => $request->time_spaet,
                'changedBy' => Auth::id(),
            ]);

            $neueSchickzeit2->save();
        }

        return redirect()->to(url('verwaltung/schickzeiten'))->with([
            'type' => 'success',
            'Meldung' => 'Zeiten gespeichert',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return RedirectResponse
     */
    public function store(SchickzeitRequest $request)
    {
        $weekdays = [
            'Montag' => '1',
            'Dienstag' => '2',
            'Mittwoch' => '3',
            'Donnerstag' => '4',
            'Freitag' => '5',
        ];

        $schickzeiten = $request->user()->schickzeiten_own()->where([
            'child_name' => $request->child,
            'weekday' => $weekdays[$request->weekday],
        ])->update([
            'changedBy' => Auth::id(),
            'deleted_at' => Carbon::now(),
        ]);

        if ($request->user()->sorgeberechtigter2 != null) {
            $schickzeiten = $request->user()->sorgeberechtigter2->schickzeiten_own()->where([
                'child_name' => $request->child,
                'weekday' => $weekdays[$request->weekday],
            ])->update([
                'changedBy' => Auth::id(),
                'deleted_at' => Carbon::now(),
            ]);
        }

        $neueSchickzeit = new Schickzeiten([
            'users_id' => $request->user()->id,
            'child_name' => $request->child,
            'weekday' => $weekdays[$request->weekday],
            'type' => $request->type,
            'time' => $request->time,
            'changedBy' => $request->user()->id,
        ]);

        $neueSchickzeit->save();

        if ($request->type == 'ab' and $request->time_spaet != '') {
            $neueSchickzeit2 = new Schickzeiten([
                'users_id' => $request->user()->id,
                'child_name' => $request->child,
                'weekday' => $weekdays[$request->weekday],
                'type' => 'spät.',
                'time' => $request->time_spaet,
                'changedBy' => $request->user()->id,
            ]);

            $neueSchickzeit2->save();
        }

        if ($neueSchickzeit->type == 'genau' and isset($neueSchickzeit->time) and $neueSchickzeit->time->format('i') != '30' and $neueSchickzeit->time->format('i') != '00') {
            $text = ' Bitte beachten Sie, dass Kinder nur zur vollen oder halben Stunde geschickt werden.';
            $type = 'warning';
        } else {
            $text = '';
            $type = 'success';
        }

        return redirect()->to(url('schickzeiten'))->with([
            'type' => $type,
            'Meldung' => 'Zeiten gespeichert.'.$text,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $day, $child)
    {
        $schickzeit = $request->user()->schickzeiten->where('weekday', '=', $day)->where('child_name', '=', $child)->sortBy('type');

        $weekdays = [
            '1' => 'Montag',
            '2' => 'Dienstag',
            '3' => 'Mittwoch',
            '4' => 'Donnerstag',
            '5' => 'Freitag',
        ];

        return view('schickzeiten.edit', [
            'child' => $child,
            'day' => $weekdays[$day],
            'day_number' => $day,
            'schickzeit' => $schickzeit->first(),
            'schickzeit_spaet' => $schickzeit->where('type', '=', 'spät.')->first(),

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function editVerwaltung($day, $child, $parent)
    {
        $schickzeit = Schickzeiten::query()->where('users_id', $parent)->where('weekday', '=', $day)->where('child_name', '=', $child)->orderBy('type')->get();

        $weekdays = [
            '1' => 'Montag',
            '2' => 'Dienstag',
            '3' => 'Mittwoch',
            '4' => 'Donnerstag',
            '5' => 'Freitag',
        ];

        return view('schickzeiten.edit_verwaltung', [
            'child' => $child,
            'parent' => $parent,
            'day' => $weekdays[$day],
            'day_number' => $day,
            'schickzeit' => $schickzeit->first(),
            'schickzeit_spaet' => $schickzeit->where('type', '=', 'spät.')->first(),

        ]);
    }

    /**
     * @param Request $request
     * @param $day
     * @param $child
     * @return RedirectResponse
     */
    public function destroy(Request $request, $day, $child)
    {
        $schickzeit = $request->user()->schickzeiten_own()->where('weekday', '=', $day)->where('child_name', '=', $child)->update([
            'changedBy' => Auth::id(),
            'deleted_at' => Carbon::now(),
        ]);
        if ($request->user()->sorgeberechtigter2 != null) {
            $schickzeit = $request->user()->sorgeberechtigter2->schickzeiten_own()->where('weekday', '=', $day)->where('child_name', '=', $child)->update([
                'changedBy' => Auth::id(),
                'deleted_at' => Carbon::now(),
            ]);
        }

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Schickzeit wurde gelöscht',
        ]);
    }

    /**
     * @param $day
     * @param $child
     * @param $parent
     * @return RedirectResponse
     */
    public function destroyVerwaltung($day, $child, $parent)
    {
        $schickzeit = Schickzeiten::query()->where('weekday', '=', $day)->where('child_name', '=', $child)->where('users_id', $parent)->update([
            'changedBy' => Auth::id(),
            'deleted_at' => Carbon::now(),
        ]);

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Schickzeit wurde gelöscht',
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download()
    {
        return Excel::download(new SchickzeitenExport, Carbon::now()->format('Ymd').'_schickzeiten.xlsx');
    }

    /**
     * @return void
     */
    public function sendReminder()
    {
        $users = User::has('schickzeiten')->get();

        foreach ($users as $user) {
            Mail::to($user->email)->queue(new SchickzeitenReminder($user->name, $user->schickzeiten));
        }
    }

    /**
     * @return RedirectResponse
     */
    public function deleteChild(User $user, string $child)
    {

        if (\auth()->id() != $user->id) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Sie können nur Ihre eigenen Kinder löschen',
            ]);
        }

        $user->schickzeiten_own()->where('child_name', Str::replace('_', ' ', $child))->update([
            'changedBy' => Auth::id(),
            'deleted_at' => Carbon::now(),
        ]);

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Kind wurde gelöscht',
        ]);

    }

    public function deleteChildVerwaltung(User $parent, string $child)
    {

        if (\auth()->user()->can('edit schickzeiten') == false) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Sie können nur Ihre eigenen Kinder löschen',
            ]);
        }

        $parent->schickzeiten()->where('child_name', Str::replace('_', ' ', $child))->update([
            'changedBy' => Auth::id(),
            'deleted_at' => Carbon::now(),
        ]);

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Kind wurde gelöscht',
        ]);

    }
}
