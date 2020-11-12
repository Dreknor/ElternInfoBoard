<?php

namespace App\Http\Controllers;

use App\Model\Losung;
use Carbon\Carbon;

class LosungController extends Controller
{
    public function getImage(){


        return response()->view('losung.image', [
            'losung' => Losung::where('date', Carbon::today())->first()
        ])
            ->header('Content-type', 'image/jepg');

    }
}
