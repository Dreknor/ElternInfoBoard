<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;

class VertretungsplanController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        $this->middleware(['permission:view vertretungsplan']);
    }

    /**
     * @return Application|View
     */
    public function index()
    {
        $gruppen = '/keine';
        foreach (auth()->user()->groups as $group) {
            $gruppen .= '/'.$group->name;
        }

        if (auth()->user()->can('view vertretungsplan all')) {
            $gruppen = '';
        }

        //$url = config('app.mitarbeiterboard').'/api/vertretungsplan'.$gruppen;
        $url = config('app.mitarbeiterboard').'/api/vertretungsplan'.$gruppen;
        $inhalt = file_get_contents($url);

        $json = json_decode($inhalt, true);

        $plan = [];

        foreach ($json as $key => $value) {
            if ($key != 'targetDate') {
                if ($key == 'news') {
                    $key = 'mitteilungen';
                }

                if ($key == 'weeks') {
                    $values = $value;
                } else {
                    $values = collect();

                    foreach ($value as $value_item) {
                        $values->push((object) $value_item);
                    }
                }

                $plan[$key] = $values;
            } else {
                $plan[$key] = $value;
            }
        }

        return view('vertretungsplan.index', $plan);
    }
}
