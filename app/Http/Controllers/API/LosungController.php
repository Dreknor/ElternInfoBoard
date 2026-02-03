<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Losung;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Class LosungController
 *
 * Controller for handling Losung related API requests.
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
     *
     * @responseField losung object The Losung entry for the current date.
     */
    public function getLosung(): JsonResponse
    {
        $losung = Losung::where('date', Carbon::now()->format('Y-m-d'))->first();

        return response()->json(['losung' => $losung], 200);
    }
}
