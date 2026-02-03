<?php

namespace App\Http\Controllers;

use App\Model\ActiveDisease;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        // Aktive meldepflichtige Erkrankungen abrufen
        $activeDiseases = Cache::remember('active_diseases', 60 * 5, function () {
            return ActiveDisease::query()
                ->where('active', true)
                ->whereDate('end', '>=', Carbon::now())
                ->with('disease')
                ->get();
        });

        return view('home', [
            'activeDiseases' => $activeDiseases,
        ]);
    }
}
