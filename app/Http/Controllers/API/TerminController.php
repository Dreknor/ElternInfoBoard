<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Termin;
use Illuminate\Http\Request;

/**
 * Class TerminController
 *
 * @group Termine
 */
class TerminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * index
     *
     * gibt alle Termine zurück
     *
     * @responseField anzahl integer Anzahl der Termine
     * @responseField termine json Liste aller Termine
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->hasPermissionTo('edit termin', 'web')) {
            $termine = Termin::all();
        } else {
            $termine = $user->termine;
        }

        $termine->unique('id');
        $termine = $termine->sortBy('start');

        return response()->json([
            'anzahl' => $termine->count(),
            'termine' => $termine], 200);
    }
}
