<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\User;
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
        //$this->middleware('auth:sanctum');
 }

 public function index()
 {
        $user = request()->user();

        if (is_null($user)) {
           $user = User::first();
        }

        $termine = $user->termine;

        $termine = $termine->unique('id');
        $termine = $termine->sortBy('start');

        $termine_fertig = $termine->map(function ($termin) {
            $termin->start = $termin->start;
            $termin->ende = $termin->ende;
            return $termin;
        });

        return response()->json($termine_fertig);
 }

}
