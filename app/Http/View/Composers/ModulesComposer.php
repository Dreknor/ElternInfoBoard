<?php


namespace App\Http\View\Composers;


use App\Model\Settings;
use Illuminate\Support\Facades\Cache;

class ModulesComposer
{
    public function compose($view)
    {


        $modules = Cache::remember('modules', 30 ,function (){
            return Settings::where('category','module')
                ->where('options', 'like', '%"active":"1"%')
                ->get();
        });

        if (!$modules){
            $modules = [];
        }

        $view->with('modules', $modules);
    }
}
