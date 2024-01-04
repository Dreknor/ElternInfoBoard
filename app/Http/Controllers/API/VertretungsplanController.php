<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class VertretungsplanController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        //$this->middleware('auth:sanctum');
    }

    /**
     * @return Application|View
     */
    public function index(Request $request)
    {
        if (config('app.mitarbeiterboard') == ""){
            return response()->json([
                'message' => 'Der Vertretungsplan ist nicht verfügbar. Bitte wenden Sie sich an den Administrator.'
            ], 404);
        }

        if (!$request->user()){

            $user = User::first();
            /*
           return response()->json([
                'message' => 'Sie sind nicht angemeldet.'
            ], 401);
            */
        } else {
            $user = $request->user();
        }


        $gruppen = '';



        if ($user->can('view vertretungsplan all')) {
            $gruppen = '';
        } else {
            foreach ($user->groups as $group) {
                $gruppen .= '/'.$group->name;
            }
        }

        $url = config('app.mitarbeiterboard').'/api/vertretungsplan'.$gruppen;
        $inhalt = file_get_contents($url);



        $json = json_decode($inhalt, true);


        $order = array('klasse' => 'asc','date' => 'asc', 'stunde' => 'desc');

        usort($json['vertretungen'], function ($a, $b) use ($order) {
            $t = array(true => -1, false => 1);
            $r = true;
            $k = 1;
            foreach ($order as $key => $value) {
                $k = ($value === 'asc') ? 1 : -1;
                $r = ($a[$key] < $b[$key]);
                if ($a[$key] !== $b[$key]) {
                    return $t[$r] * $k;
                }

            }
            return $t[$r] * $k;
        });
        /*
        usort($json['vertretungen'], function($a, $b) {
            return $a['klasse'] <=> $b['klasse'];
        });
        */

        return response()->json([
            'data' => $json,
        ], 200);
    }
}
