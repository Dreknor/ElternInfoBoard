<?php

namespace App\Http\Controllers;

use App\Model\Group;
use App\Model\Losung;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KioskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function kioskView($bereich = '')
    {

        //Elterninfos
        $Nachrichten = new Collection();
        $Listen = new Collection();

        $Gruppen = Group::where('protected', 0)->with(
            ['posts' => function ($query) {
            $query->whereDate('posts.archiv_ab', '>', Carbon::now()->startOfDay());
        }, 'listen' => function ($query) {
                $query->whereDate('listen.ende', '>', Carbon::now()->startOfDay());
            }, ]
        )->get();

        foreach ($Gruppen as $Gruppe) {
            $Nachrichten = $Nachrichten->concat($Gruppe->posts);
            $Listen = $Listen->concat($Gruppe->listen);
        }

        $Listen = $Listen->unique('id')->sortByDesc('updated_at');

        return view('kiosk.index', [
            'refresh'   => 600,
            'listen'    => $Listen,
            'Nachrichten' => $Nachrichten->unique('id')->sortByDesc('updated_at'),
            'refreshUrl' => '',
            'archiv'    => ''
        ]);
    }
}
