<?php

namespace App\Http\Controllers;

use App\Exports\SchickzeitenExport;
use App\Http\Requests\CreateChildRequest;
use App\Http\Requests\SchickzeitRequest;
use App\Mail\SchickzeitenReminder;
use App\Model\Child;
use App\Model\Schickzeiten;
use App\Model\User;
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

    /**
     *
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->schickenzeitenSetting = new SchickzeitenSetting();
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $children = auth()->user()->children;

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
    public function store(SchickzeitRequest $request, Child $child, $weekday )
    {
        if (!auth()->user()->children()->where('id', $child->id)->exists()) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Sie können nur Ihre eigenen Kinder bearbeiten.',
            ]);
        }

        // Prüfen, ob für spezifischen Tag oder Wochentag erstellt werden soll
        $specificDate = $request->input('specific_date');
        $weekday = $request->input('weekday');

        if (!$specificDate && !$weekday) {
            return redirect()->back()->with([
                'type' => 'error',
                'Meldung' => 'Sie müssen entweder einen Wochentag oder ein spezifisches Datum angeben.',
            ]);
        }
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

        $settings_ab = Carbon::createFromFormat('H:i', $this->schickenzeitenSetting->schicken_ab);
        $settings_bis = Carbon::createFromFormat('H:i', $this->schickenzeitenSetting->schicken_bis);


        switch ($request->type) {
            case 'genau':
                $time = Carbon::createFromFormat('H:i', $request->time);

                if ($time->lt($settings_ab) or $time->gt($settings_bis)) {
                    return redirect()->back()->with([
                        'type' => 'warning',
                        'Meldung' => 'Die Zeit muss zwischen '.$this->schickenzeitenSetting->schicken_ab.' und '.$this->schickenzeitenSetting->schicken_bis.' liegen',
                    ]);
                }


                $child->schickzeiten()->where('weekday', '=', $weekday)->delete();
                $child->schickzeiten()->create([
                    'weekday' => $weekday,
                    'specific_date' => $specificDate,
                    'type' => 'genau',
                    'time' => $time->format('H:i'),
                    'changedBy' => Auth::id(),
                    'users_id' => Auth::id(),
                ]);
                break;
            case 'ab':
                $ab = Carbon::createFromFormat('H:i', $request->time_ab);
                $spaet = Carbon::createFromFormat('H:i', $request->time_spaet);



                if ($ab->gt($spaet)) {
                    return redirect()->back()->with([
                        'type' => 'warning',
                        'Meldung' => 'Die Zeit für "ab" muss vor der Zeit für "spät." liegen',
                    ]);
                }

                if ($ab->lt($settings_ab) or $ab->gt($settings_bis)) {
                    return redirect()->back()->with([
                        'type' => 'warning',
                        'Meldung' => 'Die Zeit für "ab" muss zwischen '.$this->schickenzeitenSetting->schicken_ab.' und '.$this->schickenzeitenSetting->schicken_bis.' liegen',
                    ]);
                }

                if ($spaet->lt($settings_ab) or $spaet->gt($settings_bis)) {
                    return redirect()->back()->with([
                        'type' => 'warning',
                        'Meldung' => 'Die Zeit für "spät." muss zwischen '.$this->schickenzeitenSetting->schicken_ab.' und '.$this->schickenzeitenSetting->schicken_bis.' liegen',
                    ]);
                }


                $child->schickzeiten()->where('weekday', '=', $weekday)->delete();
                $child->schickzeiten()->create([
                    'weekday' => $weekday,
                    'specific_date' => $specificDate,
                    'type' => 'ab',
                    'time' => $ab->format('H:i'),
                    'changedBy' => Auth::id(),
                    'users_id' => Auth::id(),
                ]);

                $child->schickzeiten()->create([
                    'weekday' => $weekday,
                    'type' => 'spät.',
                    'time' => $spaet->format('H:i'),
                    'changedBy' => Auth::id(),
                    'users_id' => Auth::id(),
                ]);

                break;
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
        if (!auth()->user()->children()->where('id', $child->id)->exists()) {
            return redirect()->back()->with([
                'type' => 'warning',
                'Meldung' => 'Sie können nur Ihre eigenen Kinder bearbeiten.',
            ]);
        }

        $schickzeit = $child->schickzeiten()->where('weekday', '=', $day)->orderBy('type')->get();
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
            'schickzeiten' => $child->schickzeiten()->where('weekday', '=', $day)->get(),
            'schickzeit' => $schickzeit,
            'schickzeit_spaet' => $schickzeit->where('type', '=', 'spät.')->first(),
            'vorgaben' => new SchickzeitenSetting(),

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
    public function deleteChild(Child $child)
    {

        if (!auth()->user()->children()->where('id', $child->id)->exists()) {
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
                'Meldung' => 'Berrechtigung fehlt',
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
            if (!is_null($request->ab)){
                $child->schickzeiten()->create([
                    'specific_date' => Carbon::now(),
                    'type' => 'ab',
                    'time' => $request->ab,
                    'changedBy' => Auth::id(),
                    'users_id' => $child->parents()->first()->id

                ]);
            }

            if (!is_null($request->spaet)){
                $child->schickzeiten()->create([
                    'specific_date' => Carbon::now(),
                    'type' => 'spät.',
                    'time' => $request->spaet,
                    'changedBy' => Auth::id(),
                    'users_id' => $child->parents()->first()->id
                ]);
            }

        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Zeiten gespeichert',
        ]);

    }

}
