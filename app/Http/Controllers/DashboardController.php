<?php

namespace App\Http\Controllers;

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

        // Hole die nächsten 5 Termine
        $termine = Termin::query()
            ->where('start', '>=', Carbon::today())
            ->whereHas('groups', function ($query) {
                $query->whereIn('groups.id', auth()->user()->groups->pluck('id'));
            })
            ->orderBy('start')
            ->take(5)
            ->get();

        // Hole die heutige Losung
        $losung = Losung::whereDate('date', Carbon::today())->first();

        return view('dashboard.index', [
            'nachrichten' => $nachrichten,
            'termine' => $termine,
            'losung' => $losung,
            'datum' => Carbon::now(),
        ]);
    }
}

