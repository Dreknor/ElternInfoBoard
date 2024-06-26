<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Termin;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 *
 */
class TerminController extends Controller
{

 public function __construct()
 {
       $this->middleware('auth:sanctum');
 }

 public function index(Request $request)
 {

        $user = $request->user();

        if (!$user) {
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
            'termine' => $termine], 200);
 }

}
