<?php

namespace App\Http\Controllers\Anwesenheit;

use App\Exports\AnwesenheitsAbfrageExport;
use App\Http\Controllers\Controller;
use App\Jobs\AnwesenheitNotificationJob;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Groups;
use App\Settings\CareSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;


class CareController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Displays the attendance overview.
     *
     * @param bool \$showAll Decides whether to show all children or only those checked in.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View Renders the attendance view.
     */
    public function index($showAll = false)
    {
        $careSettings = new CareSetting();

        if ($showAll == 1) {
            return redirect()->route('anwesenheit.index')->withCookie(cookie()->forever('showAll', true));
        } elseif ($showAll == 'off') {
            return redirect()->route('anwesenheit.index')->withCookie(cookie()->forever('showAll', false));
        }

        $groups = Groups::query()->whereIn('id', $careSettings->groups_list)->get();
        $classes = Groups::query()->whereIn('id', $careSettings->class_list)->get();

        if ($careSettings->hide_childs_when_absent == true && !request()->cookie('showAll')) {
            $childs = Child::query()
                ->whereHas('checkIns', function ($query) {
                    $query
                        ->CheckedIn()
                        ->whereDate('date', now()->toDateString());
                })
                ->get();

        } else {
            $childs = Child::query()
                ->get();
        }

        return view('anwesenheit.index', [
            'children' => $childs,
            'groups' => $groups,
            'classes' => $classes,
            'careSettings' => $careSettings,
        ]);
    }


    /**
     * Marks a child as checked out and clears its cache entry.
     *
     * @param Child \$child Child model whose attendance is updated.
     * @return \Illuminate\Http\JsonResponse JSON response confirming success.
     */
    public function abmelden(Child $child)
    {
        $child->checkIns()
            ->where('checked_in', true)
            ->where('checked_out', false)
            ->whereDate('date', now()->toDateString())
            ->update([
                'checked_out' => true,
            ]);

        $parent = $child->parents()->first();

        if ($child->notification) {
            dispatch(new AnwesenheitNotificationJob($parent, $child->first_name, 'checkOut'));

            if ($parent->sorgorgeberechtigter2) {
                dispatch(new AnwesenheitNotificationJob($parent->sorgorgeberechtigter2, $child->first_name, 'checkOut'));
            }
        }

        Cache::forget('checkedIn' . $child->id);
        return response()->json([
            'success' => true,
        ]);

    }


    /**
     * Marks a child as checked in and dispatches relevant notifications.
     *
     * @param Child \$child Child model whose attendance is updated.
     * @return \Illuminate\Http\JsonResponse JSON response confirming success.
     */
    public function anmelden(Child $child)
    {
        $checkIn = $child->checkIns()
            ->whereDate('date', now()->toDateString())
            ->first();

        if ($checkIn) {
            $checkIn->update([
                'checked_in' => true,
                'checked_out' => false,
            ]);
        } else {
            $child->checkIns()->create([
                'checked_in' => true,
                'checked_out' => false,
                'date' => now()->toDateString(),
            ]);
        }

        $parent = $child->parents()->first();

        if ($child->notification) {

            dispatch(new AnwesenheitNotificationJob($parent, $child->first_name, 'checkIn'));

            if ($parent->sorgorgeberechtigter2) {

                dispatch(new AnwesenheitNotificationJob($parent->sorgorgeberechtigter2, $child->first_name, 'checkIn'));
            }
        }


        Cache::forget('checkedIn' . $child->id);
        Cache::forget('should_be_today' . $child->id);
        return response()->json([
            'success' => true,
        ]);

    }


    /**
     * beim Aufruf wird für alle Kinder ein CheckIn erstellt
     * @return void
     */
    public function dailyCheckIn()
    {

        if (now()->isWeekend()) {

            return;
        }

        Log::info('Starte täglichen CheckIn');

        $ferien = Cache::remember('ferien_' . Carbon::now()->year, now()->diff(Carbon::now()->endOfYear()), function () {
            $url = 'https://ferien-api.de/api/v1/holidays/SN/' . Carbon::now()->year;
            return json_decode(file_get_contents($url), true);
        });

        foreach ($ferien as $ferienTage) {
            if (now()->between($ferienTage['start'], $ferienTage['end'])) {
                Log::info('Heute ist ein Ferientag');
                $ferien = true;
            }
        }

        if ($ferien) {
            return;
        }

        $feiertage = Cache::remember('feiertage_' . Carbon::now()->year, now()->diff(Carbon::now()->endOfYear()), function () {
            $url = 'https://get.api-feiertage.de?years=' . now()->year . '&states=sn';
            return json_decode(file_get_contents($url), true);
        });

        foreach ($feiertage as $feiertag) {
            if (now()->isSameDay($feiertag['date'])) {
                Log::info('Heute ist ein Feiertag');
                return;
            }
        }

        $children = Child::query()
            ->get();

        $checkIn = [];
        foreach ($children as $child) {

            if ($child->krankmeldungToday()) {
                continue;
            }

            $checkIn[] = [
                'child_id' => $child->id,
                'checked_in' => true,
                'checked_out' => false,
                'date' => now()->toDateString(),
            ];

        }

        ChildCheckIn::query()->insert($checkIn);
        Log::info(count($checkIn) . ' abgeschlossen');


    }


    public function destroyAbfrage($date)
    {
        if (!auth()->user()->can('edit schickzeiten')) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Sie haben keine Berechtigung für diese Aktion.',
            ]);
        }

        try {
            $date = Carbon::parse($date);
        } catch (\Exception $e) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Das Datum konnte nicht gelesen werden.',
            ]);
        }

        ChildCheckIn::query()
            ->whereDate('date', $date->toDateString())
            ->delete();

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Die Abfrage wurde gelöscht.',
        ]);
    }

    public function storeAbfrage (Request $request){
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'lock_at' => 'nullable|date',
        ]);

        $date_start = Carbon::parse($request->date_start);
        $date_end = $request->date_end ? Carbon::parse($request->date_end) : $date_start->copy();
        $lock_at = $request->lock_at ? Carbon::parse($request->lock_at) : null;

        $careSettings = new CareSetting();

        $children = Child::query()
            ->whereIn('class_id', $careSettings->class_list)
            ->whereIn('group_id', $careSettings->groups_list)
            ->with(['checkIns' => function ($query) use ($date_start, $date_end) {
                $query->whereBetween('date', [$date_start->toDateString(), $date_end->toDateString()]);
            }])
            ->get();

        $checkIns = [];

        for ($date = $date_start; $date->lte($date_end); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($children as $child) {
                if ($child->checkIns->count() > 0) {
                    continue;
                }


                $checkIns[] = [
                    'child_id' => $child->id,
                    'checked_in' => false,
                    'checked_out' => false,
                    'date' => $date_start->toDateString(),
                    'should_be' => false,
                    'lock_at' => $lock_at ? $lock_at->toDateString() : $date_start->copy()->subDay()->toDateString(),
                ];
            }
        }

        ChildCheckIn::query()->insert($checkIns);

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Die Abfrage wurde erstellt.',
        ]);
    }

    public function downloadAbfrageAnwesenheit (Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
        ]);

       $dates = ChildCheckIn::query()
            ->whereBetween('date', [$request->date_start, $request->date_end])
            ->orderBy('date')
            ->pluck('date')
            ->unique();




        return Excel::download(new AnwesenheitsAbfrageExport($request->date_start, $request->date_end, $dates), 'Anwesenheitsabfrage.xlsx');



    }
}
