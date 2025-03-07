<?php

namespace App\Http\Controllers;

use App\Exports\SchickzeitenExport;
use App\Http\Requests\CreateChildRequest;
use App\Http\Requests\SchickzeitRequest;
use App\Mail\SchickzeitenReminder;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Schickzeiten;
use App\Model\User;
use App\Settings\CareSetting;
use App\Settings\SchickzeitenSetting;
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
use function _PHPStan_9488d3497\RingCentral\Psr7\_caseless_remove;

class SchickzeitenController extends Controller
{

    protected SchickzeitenSetting $schickenzeitenSetting;
    protected CareSetting $careSettings;

    /**
     *
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->schickenzeitenSetting = new SchickzeitenSetting();
        $this->careSettings = new CareSetting();

    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $children = auth()->user()->children();
        $allowedClasses = $this->careSettings->class_list;
        $allowedGroups = $this->careSettings->groups_list;

        $children = $children->filter(function ($child) use ($allowedClasses, $allowedGroups) {
            return in_array($child->class_id, $allowedClasses) && in_array($child->group_id, $allowedGroups);
        });

        $children = $children->load(['checkIns' => fn ($query) => $query->where('date', '>', today())]);


        $weekdays = [
            '1' => 'Montag',
            '2' => 'Dienstag',
            '3' => 'Mittwoch',
            '4' => 'Donnerstag',
            '5' => 'Freitag',
        ];

        return view('schickzeiten.index', [
            'children' => $children,
            'weekdays' => $weekdays,
            'vorgaben' => new SchickzeitenSetting(),
            'careSettings' => $this->careSettings,
        ]);
    }

    public function anwesenheitTrue(ChildCheckIn $childCheckIn)
    {
        if (!auth()->user()->children()->contains($childCheckIn->child)) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Sie können nur Ihre eigenen Kinder bearbeiten.',
            ]);
        }

        if ($childCheckIn->lock_at && $childCheckIn->lock_at->lt(now())) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Anwesenheit kann nicht mehr geändert werden.',
            ]);
        }

        $childCheckIn->update([
            'should_be' => true,
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Anwesenheit wurde bestätigt',
        ]);
    }

    public function anwesenheitFalse(ChildCheckIn $childCheckIn)
    {

        if (!auth()->user()->children()->contains($childCheckIn->child)) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Sie können nur Ihre eigenen Kinder bearbeiten.',
            ]);
        }

        $childCheckIn->update([
            'should_be' => false,
        ]);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Anwesenheit wurde bestätigt',
        ]);
    }

    /**
     * @return Application|Factory|View|RedirectResponse
     */
    public function indexVerwaltung()
    {

        if (\auth()->user()->can('edit schickzeiten') == false) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berrechtigung fehlt',
            ]);
        }

        $careSettings = new CareSetting();



        $children = Child::query()
            ->whereIn('group_id', $careSettings->groups_list)
            ->whereIn('class_id', $careSettings->class_list)
            ->with('schickzeiten')->get();


        $abfragen = ChildCheckIn::query()
            ->where('date', '>', today())
            ->get(['date', 'should_be', 'child_id']);


        $abfragen_daten = [];

        foreach ($abfragen->groupBy('date') as $datum) {
            $date = $datum->first()->date->format('Y-m-d');

            $abfragen_daten[$date] = count($datum->where('should_be', true));


        }


        $weekdays = [
            '1' => 'Montag',
            '2' => 'Dienstag',
            '3' => 'Mittwoch',
            '4' => 'Donnerstag',
            '5' => 'Freitag',
        ];


        return view('schickzeiten.index_verwaltung', [
            'children' => $children,
            'weekdays' => $weekdays,
            'abfragen' => $abfragen_daten,
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
    public function store(SchickzeitRequest $request, Child $child, $weekday = null)
    {
        if (!auth()->user()->children()->contains($child)) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Sie können nur Ihre eigenen Kinder bearbeiten.',
            ]);
        }

        // Prüfen, ob für spezifischen Tag oder Wochentag erstellt werden soll
        $specificDate = $request->input('specific_date');

        if (!$specificDate && !$weekday) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Sie müssen entweder einen Wochentag oder ein spezifisches Datum angeben.',
            ]);
        }

        if ($weekday){
            switch ($weekday) {
                case 'Montag':
                    $weekday = 1;
                    break;
                case 'Dienstag':
                    $weekday = 2;
                    break;
                case 'Mittwoch':
                    $weekday = 3;
                    break;
                case 'Donnerstag':
                    $weekday = 4;
                    break;
                case 'Freitag':
                    $weekday = 5;
                    break;
                default:
                    return redirect()->back()->with([
                        'type' => 'warning',
                        'Meldung' => 'Wochentag nicht gefunden',
                    ]);
                    break;
            }
        }

        $settings_ab = Carbon::createFromFormat('H:i', $this->schickenzeitenSetting->schicken_ab);
        $settings_bis = Carbon::createFromFormat('H:i', $this->schickenzeitenSetting->schicken_bis);

        if ($request->type == 'genau'){
            $time = Carbon::createFromFormat('H:i', $request->time);

            if ($time->lt($settings_ab) || $time->gt($settings_bis)) {
                return redirect()->back()->with([
                    'type' => 'warning',
                    'Meldung' => 'Ungültige Zeit',
                ]);
            }

            if ($weekday){
                $child->schickzeiten()->where('weekday', '=', $weekday)->delete();
            } else {
                $child->schickzeiten()->where('specific_date', '=', $specificDate)->delete();
            }

            $child->schickzeiten()->create([
                'weekday' => $weekday,
                'specific_date' => $specificDate,
                'type' => $request->type,
                'time' => $request->time,
                'changedBy' => Auth::id(),
                'users_id' => $child->parents()->first()->id
            ]);

        } else {

            if ($request->time_ab == '' ) {
                return redirect()->back()->with([
                    'type' => 'warning',
                    'Meldung' => 'Bitte geben Sie an, ab wann das Kind gehen darf',
                ]);
            }
            $time_ab = Carbon::createFromFormat('H:i', $request->time_ab);

            if ($request->time_spaet != '') {
                $time_spaet = Carbon::createFromFormat('H:i', $request->time_spaet);
            }


            if ($time_ab->lt($settings_ab) || $time_ab->gt($settings_bis) || (isset($time_spaet) && ($time_spaet->lt($settings_ab) || $time_spaet->gt($settings_bis)))) {
                return redirect()->back()->with([
                    'type' => 'warning',
                    'Meldung' => 'Ungültige Zeit',
                ]);
            }

            if ($weekday){
                $child->schickzeiten()->where('weekday', '=', $weekday)->delete();
            } else {
                $child->schickzeiten()->where('specific_date', '=', $specificDate)->delete();
            }

            $child->schickzeiten()->create([
                'weekday' => $weekday,
                'specific_date' => $specificDate,
                'type' => 'ab',
                'time_ab' => $request->time_ab,
                'time_spaet' => $time_spaet ?? null,
                'changedBy' => Auth::id(),
                'users_id' => $child->parents()->first()->id
            ]);

        }

        return redirect(url('schickzeiten'))->with([
            'type' => 'success',
            'Meldung' => 'Zeiten gespeichert',
        ]);
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $day, Child $child)
    {
        if (!auth()->user()->children()->contains($child)) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Sie können nur Ihre eigenen Kinder bearbeiten.',
            ]);
        }

        $schickzeit = $child->schickzeiten()->where('weekday', '=', $day)->first();
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
            'schickzeit' => $schickzeit,
            'vorgaben' => new SchickzeitenSetting(),

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function editVerwaltung($day, Child $child)
    {

        $schickzeiten = Schickzeiten::query()->where('child_id', '=', $child->id)->where('weekday', '=', $day)->get();

        $weekdays = [
            '1' => 'Montag',
            '2' => 'Dienstag',
            '3' => 'Mittwoch',
            '4' => 'Donnerstag',
            '5' => 'Freitag',
        ];

        return view('schickzeiten.edit_verwaltung', [
            'child' => $child,
            'day' => $weekdays[$day],
            'day_number' => $day,
            'schickzeiten' => $schickzeiten,

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
    public function deleteChild(Child $child)
    {

        if (!auth()->user()->children()->contains($child)) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Sie können nur Ihre eigenen Kinder bearbeiten.',
            ]);
        }

        $child->schickzeiten()->delete();


        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Schickzeiten wurden gelöscht',
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

    public function storeDailyVerwaltung(SchickzeitRequest $request, Child $child)
    {
        if (!auth()->user()->can('edit schickzeiten')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Berechtigung fehlt',
            ]);
        }


        $child->schickzeiten()->where('specific_date', '=', Carbon::now()->toDateString())->delete();

        if ($request->type == 'genau'){
            $child->schickzeiten()->create([
                'specific_date' => Carbon::now(),
                'type' => $request->type,
                'time' => $request->time,
                'changedBy' => Auth::id(),
                'users_id' => $child->parents()->first()->id

            ]);
        } else {
            $child->schickzeiten()->create([
                'specific_date' => Carbon::now(),
                'type' => 'ab',
                'time_ab' => $request->ab,
                'time_spaet' => $request->spaet,
                'changedBy' => Auth::id(),
                'users_id' => $child->parents()->first()->id
            ]);

        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Zeiten gespeichert',
        ]);

    }

    public function destroySchickzeit(Schickzeiten $schickzeit)
    {
       if (!auth()->user()->children()->contains($schickzeit->child) && !auth()->user()->can('edit schickzeiten')) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Sie können nur Ihre eigenen Kinder bearbeiten.',
            ]);
        }

        $schickzeit->delete();

        return redirect()->back()->with([
            'type' => 'warning',
            'Meldung' => 'Schickzeit wurde gelöscht',
        ]);
    }

    public function storeAbfrageAnwesenheit(Request $request)
    {
        if (!auth()->user()->can('edit schickzeiten')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Sie haben keine Berechtigung für diese Aktion.',
            ]);
        }

        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'nullable|date',
            'child_id' => 'required|exists:children,id',
        ]);

        $date_start = Carbon::parse($request->date_start);
        $date_end = Carbon::parse($request->date_end);

        $child = Child::find($request->child_id);

        $child->load(['checkIns' => fn ($query) => $query->where('date', '>=', $date_start)->where('date', '<=', $date_end)]);

        $checkIn = [];

        for ($date = $date_start; $date->lte($date_end); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }
           $child->checkIns()->updateOrCreate([
                'date' => $date->toDateString(),
            ], [
                'checked_in' => false,
                'checked_out' => false,
                'should_be' => true,
            ]);
        }

        ChildCheckIn::query()->insert($checkIn);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Gespeichert',
        ]);
    }
}
