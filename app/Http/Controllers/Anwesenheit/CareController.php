<?php

namespace App\Http\Controllers\Anwesenheit;

use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Groups;
use App\Notifications\Push;
use App\Settings\CareSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CareController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Anwesenheitsübersicht
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
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
     * abmelden eines Kindes
     *
     * @param Child $child
     * @return JsonResponse
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

        if ($parent->can('testing')) {
            $parent->notify(new Push('Abmeldung','Ihr Kind ' . $child->first_name . ' wurde abgemeldet.'));
        }


        Cache::forget('checkedIn' . $child->id);
        return response()->json([
            'success' => true,
        ]);

    }

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
            $parent->notify(new Push('Anmeldung im Hort','Ihr Kind ' . $child->first_name . ' wurde im Hort angemeldet.'));
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
