<?php

namespace App\Http\View\Composers;

use App\Model\Losung;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LosungComposer
{
    public function compose($view): void
    {
        $expire = now()->diffInSeconds(now()->endOfDay());

        $losung = Cache::remember('losung', $expire, function () {
            return Losung::where('date', Carbon::today())->first();
        });

        $view->with('losung', $losung);
    }
}
