<?php


namespace App\Http\View\Composers;


use App\Model\Losung;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ReinigungComposer
{
    public function compose($view)
    {


        $expire = now()->diffInSeconds(now()->endOfDay());

        $reinigung = Cache::remember('reinigung'.auth()->id(), $expire ,function (){
            $reinigung = auth()->user()->Reinigung()->whereBetween('datum', [Carbon::now()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()])->first();
                if ($reinigung == "" and auth()->user()->sorgeberechtigter2 != ""){
                    $reinigung = auth()->user()->sorgeberechtigter2->Reinigung()->whereBetween('datum', [Carbon::now()->startOfWeek(), Carbon::now()->addWeek()->endOfWeek()])->first();
                }

            return $reinigung;
        });


        $view->with('reinigung', $reinigung);
    }
}
