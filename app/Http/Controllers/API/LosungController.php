<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportLosungenRequest;
use App\Imports\LosungenImport;
use App\Model\Losung;
use App\Model\Module;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class LosungController
 *
 * Controller for handling Losung related API requests.
 *
 */
class LosungController extends Controller
{

    /**
     * Retrieve the Losung for the current date.
     *
     * This method fetches the Losung entry from the database where the date matches the current date.
     * The result is returned as a JSON response.
     *
     * @group Losungen
     * @responseField losung object The Losung entry for the current date.
     *
     *
     * @return JsonResponse
     */
    public function getLosung(): JsonResponse
    {
        $losung = Losung::where('date', Carbon::now()->format('Y-m-d'))->first();
        return response()->json(['losung' => $losung], 200);
    }
}
