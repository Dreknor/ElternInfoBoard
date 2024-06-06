<?php

namespace App\Http\View\Composers;

use App\Model\ActiveDisease;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DiseaseComposer
{
    public function compose($view): void
    {

        if (auth()->user()->can('manage diseases')) {
            $disaeses = ActiveDisease::whereDate('end', '>=', Carbon::today())->with('disease')->orderBy('end')->get();
        } else {
            $disaeses = ActiveDisease::whereDate('end', '>=', Carbon::today())->active()->with('disease')->orderBy('end')->get()->unique('disease_id');

        }

        $view->with('diseases', $disaeses);
    }
}
