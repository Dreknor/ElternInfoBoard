<?php

namespace App\Http\Controllers;

use App\Model\Group;
use App\Model\Losung;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KioskController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    public function kioskView($bereich = ""){

        //Elterninfos
        $Nachrichten = new Collection();


        $Gruppen = Group::where('protected', 0)->with(['posts' => function ($query){
            $query->whereDate('posts.archiv_ab', '>', Carbon::now()->startOfDay());
        }])->get();

        foreach ($Gruppen as $Gruppe){
            $Nachrichten = $Nachrichten->concat($Gruppe->posts);
        }

        return view('layouts.kiosk',[
            'refresh'   => 600,
            'module'    => ['losung','uhr', 'bilder', 'elterninfo'],
            'elterninfo' => $Nachrichten->unique('id')->sortByDesc('updated_at'),
            'losung'    =>  Losung::where('date', Carbon::today())->first()
        ]);
    }
}
