<?php


namespace App\Http\View\Composers;


use App\Model\Losung;
use App\Model\Termin;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TermineComposer
{
    public function compose($view)
    {


        $expire = now()->diffInSeconds(now()->endOfDay());


        $termine = Cache::remember('termine'.auth()->id(), $expire ,function (){

            $expire = now()->diffInSeconds(now()->endOfDay());



            //Termine holen
            if (!auth()->user()->can('edit termin') and !auth()->user()->can('view all')) {
                $Termine = auth()->user()->termine;
            } else {
                $Termine = Termin::all();
                $Termine = $Termine->unique('id');
            }

            $Termine = $Termine->sortBy('start');


            //Termine aus Listen holen
            $listen_termine = auth()->user()->listen_eintragungen()->whereDate('termin', '>', Carbon::now()->startOfDay())->get();

            //Ergänze Listeneintragungen
            if (!is_null($listen_termine) and count($listen_termine) > 0) {
                foreach ($listen_termine as $termin) {
                    $newTermin = new Termin([
                        "terminname" => $termin->liste->listenname,
                        "start" => $termin->termin,
                        "ende" => $termin->termin->copy()->addMinutes($termin->liste->duration),
                        "fullDay" => null
                    ]);
                    $Termine->push($newTermin);
                }
            }

            //Listentermine von Sorg2
            if (!is_null(auth()->user()->sorgeberechtigter2)){
                foreach (auth()->user()->sorgeberechtigter2->listen_eintragungen()->whereDate('termin', '>', Carbon::now()->startOfDay())->get() as $termin) {
                    $newTermin = new Termin([
                        "terminname" => $termin->liste->listenname,
                        "start" => $termin->termin,
                        "ende" => $termin->termin->copy()->addMinutes($termin->liste->duration),
                        "fullDay" => null
                    ]);
                    $Termine->push($newTermin);
                }
            }

            $Termine = $Termine->unique('id');
            $Termine = $Termine->sortBy('start');



            return $Termine;
        });


        $view->with('termine', $termine);
    }
}
