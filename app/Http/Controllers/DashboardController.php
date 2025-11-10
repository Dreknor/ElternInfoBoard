<?php

namespace App\Http\Controllers;

use App\Model\Child;
use App\Model\Losung;
use App\Model\Post;
use App\Model\Termin;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return View
     */
    public function index()
    {
        // Hole nur die neuesten 5 Nachrichten

        if (auth()->user()->can('view all')) {
            $nachrichten = Post::query()
                ->whereNull('archiv_ab')
                ->orderBy('created_at', 'desc')
                ->take(5);

            $termine = Termin::query()
                ->where('start', '>=', Carbon::today())
                ->orderBy('start')
                ->take(5);

        } else {
            $nachrichten = Post::query()
                ->where('released', 1)
                ->where(function ($query) {
                    $query->whereNull('archiv_ab')
                        ->orWhere('archiv_ab', '>', Carbon::now());
                })
                ->whereHas('groups', function ($query) {
                    $query->whereIn('groups.id', auth()->user()->groups->pluck('id'));
                })
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $termine = Termin::query()
                ->where('start', '>=', Carbon::today())
                ->whereHas('groups', function ($query) {
                    $query->whereIn('groups.id', auth()->user()->groups->pluck('id'));
                })
                ->orderBy('start')
                ->take(5)
                ->get();
        }



        // Hole die heutige Losung
        $losung = Losung::whereDate('date', Carbon::today())->first();

        // Hole die Kinder des Benutzers, die den Care-Scope erfüllen
        $careChildren = auth()->user()->children_rel()
            ->care()
            ->orderBy('first_name')
            ->get();

        return view('dashboard.index', [
            'nachrichten' => $nachrichten,
            'termine' => $termine,
            'losung' => $losung,
            'datum' => Carbon::now(),
            'careChildren' => $careChildren,
        ]);
    }
}

