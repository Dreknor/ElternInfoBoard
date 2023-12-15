<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportLosungenRequest;
use App\Imports\LosungenImport;
use App\Model\Losung;
use App\Model\Settings;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Maatwebsite\Excel\Facades\Excel;

class LosungController extends Controller
{


    public function getLosung()
    {
        $losung = Losung::where('date', Carbon::now()->format('Y-m-d'))->first();
        return response()->json($losung, 200);
    }
}
