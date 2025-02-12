<?php

namespace App\Http\Controllers\Anwesenheit;

use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Groups;
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
    public function index()
    {
        $careSettings = new CareSetting();

       $groups = Groups::query()->whereIn('id', $careSettings->groups_list)->get();
       $classes = Groups::query()->whereIn('id', $careSettings->class_list)->get();

        if ($careSettings->hide_childs_when_absent) {
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
