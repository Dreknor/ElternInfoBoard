<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VertretungsplanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view vertretungsplan']);
    }

    public function index (){
        $gruppen = "/keine";
        foreach (auth()->user()->groups as $group) {
            $gruppen .= "/" . $group->name;
        }

        if (auth()->user()->can('view vertretungsplan all')) {
            $gruppen = "";
        }


        //$url = config('app.mitarbeiterboard').'/api/vertretungsplan'.$gruppen;
        $url = config('app.mitarbeiterboard') . '/api/vertretungsplan' . $gruppen;

        $json = json_decode(file_get_contents($url), true);

        $plan = [];

        foreach ($json as $key => $value) {
            if ($key != 'targetDate') {
                if ($key == 'news') {
                    $key = "mitteilungen";
                }
                $values = collect();

                foreach ($value as $value_item) {
                    $values->push((object)$value_item);
                }
                $plan[$key] = $values;
            } else {
                $plan[$key] = $value;
            }

        }

        return view('vertretungsplan.index', $plan);
    }
}
