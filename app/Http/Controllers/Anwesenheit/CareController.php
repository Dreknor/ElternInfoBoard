<?php

namespace App\Http\Controllers\Anwesenheit;

use App\Http\Controllers\Controller;
use App\Jobs\AnwesenheitNotificationJob;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Groups;
use App\Settings\CareSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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

            if ($parent->sorgorgeberechtigter2){
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

        if ($parent->can('testing')) {

           dispatch(new AnwesenheitNotificationJob($parent, $child->first_name , 'checkIn'));

            if ($parent->sorgorgeberechtigter2){

                dispatch(new AnwesenheitNotificationJob($parent->sorgorgeberechtigter2, $child->first_name, 'checkIn'));
            }
        }



        Cache::forget('checkedIn' . $child->id);
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

        $ferien = Cache::remember('ferien_' . Carbon::now()->year, now()->diff(Carbon::now()->endOfYear()), function () {
            $url = 'https://ferien-api.de/api/v1/holidays/SN/' . Carbon::now()->year;
            return json_decode(file_get_contents($url), true);
        });

        foreach ($ferien as $ferienTage) {
            if (now()->between($ferienTage['start'], $ferienTage['end'])) {
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


    }
}
