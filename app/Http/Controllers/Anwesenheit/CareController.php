<?php

namespace App\Http\Controllers\Anwesenheit;

use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Settings\CareSetting;
use Illuminate\Support\Facades\Cache;

class CareController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $careSettings = new CareSetting();

        $groups = ['Frühling', 'Sommer', 'Herbst', 'Winter'];
        $classes = ['Klasse 1', 'Klasse 2', 'Klasse 3', 'Klasse 4'];
        dump($careSettings->hide_childs_when_absent);
        if ($careSettings->hide_childs_when_absent) {
            $childs = Child::query()
                ->whereHas('checkIns', function ($query) {
                    $query->where('checked_in', true)
                        ->where('checked_out', false)
                        ->whereDate('date', now()->toDateString());
                })
                ->get();

            dump($childs);
        } else {

            $childs = Child::query()
                ->get();
            dump($childs);

        }



        return view('anwesenheit.index', [
            'children' => $childs,
            'groups' => $groups,
            'classes' => $classes,
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
